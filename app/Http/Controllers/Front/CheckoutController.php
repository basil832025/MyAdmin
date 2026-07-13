<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\LoyaltyService;
use App\Models\Shop\Order;
use App\Models\Shop\Client;
use App\Models\Shop\ClientAddress;
use App\Models\Shop\PaypartsBank;
use App\Enums\OrderStatus;
use App\Models\Location;
use App\Enums\PaymentMethodEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Shop\PromoCode;
use App\Services\OrderPricing;
use App\Models\Shop\OrderAdjustment;
use App\Models\Shop\OrderItem;
use App\Models\Shop\Product;
//use App\Models\Shop\FixedDiscount;
use App\Models\Shop\TimeDiscount;
use App\Models\Setting;
use App\Services\DeliveryCalculationService;
use App\Services\ScheduleV2Service;
use App\Services\LiqPayService;
use App\Services\PrivatBankPaypartsService;
use App\Mail\OrderNotificationMail;
use App\Mail\OrderClientMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly LoyaltyService $loyalty,
        private readonly DeliveryCalculationService $deliveryCalculation,
        private readonly ScheduleV2Service $scheduleV2,
    ) {}

/**
 * Страница оформления заказа.
 * Здесь считаем доступные бонусы и прокидываем в $totals для _summary.blade.php
 */
public function index()
{
    // Проверяем авторизацию - если не авторизован, перенаправляем на страницу авторизации
    if (!Auth::check()) {
        // Сохраняем URL checkout в сессии для редиректа после авторизации
        session(['checkout.redirect_url' => request()->url()]);

        $locale = app()->getLocale();
        $routeName = in_array($locale, ['ru', 'en'], true)
            ? 'localized.auth.show'
            : 'auth.show';
        $routeParams = in_array($locale, ['ru', 'en'], true)
            ? ['locale' => $locale]
            : [];

        return redirect()->route($routeName, $routeParams);
    }
    // Загружаем сохраненные данные из сессии
    $sessionData = session('checkout.form_data', []);

    $items  = $this->cart->items();
    $info   = $this->cart->info();

    if ($this->isCartEmpty($items, $info)) {
        $this->resetCheckoutDynamicState();
        $locale = app()->getLocale();
        $routeName = in_array($locale, ['ru', 'en'], true)
            ? 'localized.home'
            : 'home';
        $routeParams = in_array($locale, ['ru', 'en'], true)
            ? ['locale' => $locale]
            : [];

        return redirect()->route($routeName, $routeParams);
    }

    $sessionData = $this->syncCheckoutStateWithCart($items, $sessionData);
    // 1) Точки самовывоза из bs_locations
    $locations = Location::query()
        ->where('is_active', 1)
        ->orderBy('sort')
        ->get();
    // Сумма товаров и скидка — подстрой под свою структуру info()
    $itemsTotal = (float)($info['items_total'] ?? $info['total_price'] ?? 0);
    $discountBase = (float)($info['discount'] ?? 0);
    $promoDiscount = (float) session('checkout.promo_discount', 0);
    $discount   = max(0, $discountBase + $promoDiscount);

    $client   = Auth::user();          // твоя модель клиента
    $clientId = $client?->id;
    $phone    = $client?->phone ?? null;

    $useBonusChecked = (bool) ($sessionData['use_bonus'] ?? true);
    $bonusUsedFromSession = $useBonusChecked
        ? (float) ($sessionData['bonus_amount'] ?? 0)
        : 0.0;

    $balance = $this->loyalty->getBalance($clientId, $phone);
    $limit   = $this->loyalty->getBonusLimitForOrder($itemsTotal, $discount, $balance);
    $bonusUsed = max(0, min($bonusUsedFromSession, $limit));
    // 👇 база для начисления и теоретические бонусы
    $bonusEarn = $this->loyalty->previewEarnForCart($itemsTotal, $discount, $bonusUsed);

    $totals = [
        'qty'          => (int)($info['qty'] ?? 0),
        'items_total'  => $itemsTotal,
        'discount'     => $discount,
        'grand_total'  => max($itemsTotal - $discount - $bonusUsed, 0),
        'bonus_points' => $balance,
        'bonus_limit'  => $limit,
        'bonus_earn'   => $bonusEarn,
        'bonus_used'   => $bonusUsed,
    ];
    $productIds  = collect($items)->pluck('product_id');

    // --- ФИКСИРОВАННЫЕ АКЦИИ (типа "Именинникам") ---
 /*   $fixedDiscounts = FixedDiscount::query()
        ->where('is_active', true)
        ->forAll()                   // твой scopeForAll()
        ->get()
        ->filter(function (FixedDiscount $d) use ($clientId, $productIds) {
            return $d->canApply($clientId) && $d->hasEligibleProducts($productIds);
        });*/

    // --- АКЦИИ ПО ВРЕМЕНИ ---
    $timeDiscounts = TimeDiscount::query()
        ->where('is_active', true)
        ->get()
        ->filter(function (TimeDiscount $d) use ($productIds) {
            return $d->hasEligibleProducts($productIds);
        });

    // Получаем параметры доставки из сессии
    $shippingMethod = $sessionData['shipping_method'] ?? 'delivery';
    $deliveryMode = $sessionData['delivery_mode'] ?? 'asap';
    $deliveryDate = $sessionData['delivery_date'] ?? null;
    $deliveryTime = $sessionData['delivery_time'] ?? null;
    $paypartsCheckoutTotal = $this->calculateCheckoutPaypartsTotal(
        $itemsTotal,
        $discount,
        $bonusUsed,
        (string) $shippingMethod,
        (float) ($sessionData['shipping_price'] ?? 0)
    );

    // Получаем продукты с характеристиками для проверки диаметров
    $products = Product::with('characteristicValues')
        ->whereIn('id', $productIds)
        ->get();

    // Генерируем временные интервалы для доставки (legacy fallback)
    $timeIntervals = $this->getDeliveryTimeIntervals($locations);

    $primaryLocation = $locations->firstWhere('schedule_v2_enabled', true) ?? $locations->first();
    $scheduleV2Payload = $primaryLocation
        ? $this->scheduleV2->buildPayload($primaryLocation, now('Europe/Kyiv'), 14)
        : ['enabled' => false, 'timezone' => 'Europe/Kyiv', 'now' => now('Europe/Kyiv')->toIso8601String(), 'methods' => []];

// локаль сайта
    $locale = app()->getLocale();
    // приводим к единому массиву для шаблона
    $availablePromos = collect()
   /*     ->merge(
            $fixedDiscounts->map(function (FixedDiscount $d) use ($locale) {
                // берём название по локали
                $name = $d->getTranslation('name', $locale);
                $p    = number_format((float)$d->percent, 2, '.', '');

                return [
                    'id'          => $d->id,
                    'type'        => 'fixed',
                    // Например: "Birthday 20% (−20.00%)"
                    'label'       => "{$name} (−{$p}%)",
                    'description' => $d->description ?? null,
                ];
            })
        )*/
        ->merge(
            $timeDiscounts->map(function (TimeDiscount $d) use ($locale, $shippingMethod, $deliveryMode, $deliveryDate, $deliveryTime, $products) {
                $name = $d->getTranslation('name', $locale);
                $p    = number_format((float)$d->percent, 2, '.', '');

                // Проверяем условия для акции
                $isActive = $this->checkPromoConditions($d, $shippingMethod, $deliveryMode, $deliveryDate, $deliveryTime, $products);
                $previewDiscount = $this->calculateCheckoutTimeDiscountPreview($d, $products, $this->cart->items());
                $isActive = $isActive && $previewDiscount > 0;

                return [
                    'id'          => $d->id,
                    'type'        => 'time',
                    // Например: "Happy hours 50%"
                    'label'       => "{$name} ({$p}%)",
                    'description' => $d->description ?? null,
                    'is_active'   => $isActive,
                ];
            })
        );

    $paypartsAllowed = $this->isPaypartsEnabledForClient($client);
    $paypartsBanks = $paypartsAllowed
        ? $this->availablePaypartsBanksForCheckout($client, $paypartsCheckoutTotal)
        : collect();
    $paypartsEnabled = $paypartsAllowed && $paypartsBanks->isNotEmpty();
    return view(front_view('checkout.index'), [
        'items'     => $items,
        'totals'    => $totals,
        'locations' => $locations,
        'availablePromos' => $availablePromos,
        'sessionData' => $sessionData,
        'timeIntervals' => $timeIntervals,
        'scheduleV2' => $scheduleV2Payload,
        'paypartsBanks' => $paypartsBanks,
        'paypartsEnabled' => $paypartsEnabled,
    ]);
}

public function paypartsOptions(Request $request)
{
    $client = Auth::user();
    $amount = max(0, round((float) $request->input('amount', 0), 2));
    $paypartsAllowed = $this->isPaypartsEnabledForClient($client);
    $paypartsBanks = $paypartsAllowed
        ? $this->availablePaypartsBanksForCheckout($client, $amount)
        : collect();

    $sessionData = array_merge(session('checkout.form_data', []), array_filter([
        'payment_method' => $request->input('payment_method'),
        'payparts_bank_id' => $request->input('payparts_bank_id'),
        'payparts_plan_key' => $request->input('payparts_plan_key'),
        'payparts_financial_phone' => $request->input('payparts_financial_phone'),
    ], static fn ($value): bool => $value !== null && $value !== ''));

    return response()->json([
        'ok' => true,
        'html' => view(front_view('checkout.partials._payment-methods'), [
            'sessionData' => $sessionData,
            'paymentMethod' => $request->input('payment_method', $sessionData['payment_method'] ?? 'liqpay'),
            'paypartsBanks' => $paypartsBanks,
            'paypartsEnabled' => $paypartsAllowed && $paypartsBanks->isNotEmpty(),
        ])->render(),
    ]);
}

/**
 * Получить временные интервалы для доставки из графика работы
 *
 * @param \Illuminate\Support\Collection $locations
 * @return array Массив интервалов вида ["09:00-09:15", "09:15-09:30", ...]
 */
private function getDeliveryTimeIntervals($locations): array
{
    $startTime = '08:30';
    $endTime = '21:00';

    // Пытаемся найти график доставки во всех активных точках
    if ($locations->isNotEmpty()) {
        foreach ($locations as $location) {
            $schedule = $location->schedule ?? null;


            if (is_array($schedule) && !empty($schedule)) {
                // Ищем элемент со slug = "delivery"
                foreach ($schedule as $index => $scheduleItem) {
                    // Filament Repeater создает структуру с 'data' и верхним уровнем
                    // Проверяем slug на верхнем уровне (обрезаем пробелы)
                    $slug = trim($scheduleItem['slug'] ?? '');
                    if ($slug !== 'delivery') {
                        continue;
                    }

                    // Проверяем, активен ли график (сначала верхний уровень, потом data)
                    $isActive = $scheduleItem['is_active'] ?? ($scheduleItem['data']['is_active'] ?? true);
                    if ($isActive === false) {
                        continue;
                    }

                    // Получаем время из украинского перевода по умолчанию
                    // Сначала проверяем верхний уровень (там правильное значение)
                    $timeData = $scheduleItem['time'] ?? $scheduleItem['data']['time'] ?? null;
                    $timeStr = null;

                    // time может быть массивом локализованных значений или строкой
                    // Filament может создавать двойную вложенность: time[uk][uk]
                    if (is_array($timeData)) {
                        // Сначала пробуем двойную вложенность для uk (time[uk][uk])
                        if (isset($timeData['uk']['uk']) && is_string($timeData['uk']['uk'])) {
                            $timeStr = $timeData['uk']['uk'];
                        } elseif (isset($timeData['uk']) && is_string($timeData['uk'])) {
                            // Прямой доступ time[uk]
                            $timeStr = $timeData['uk'];
                        }
                    } elseif (is_string($timeData)) {
                        $timeStr = $timeData;
                    }

                    if ($timeStr) {
                        // Парсим строку вида "з 09:00 до 21:00", "09:00-21:00", "с 09:00 до 21:00"
                        // Ищем два времени в формате HH:MM
                        if (preg_match_all('/(\d{1,2}):(\d{2})/u', $timeStr, $timeMatches, PREG_SET_ORDER)) {
                            if (count($timeMatches) >= 2) {
                                // Есть два времени - это начало и конец
                                $startTime = sprintf('%02d:%02d', (int)$timeMatches[0][1], (int)$timeMatches[0][2]);
                                $endTime = sprintf('%02d:%02d', (int)$timeMatches[1][1], (int)$timeMatches[1][2]);
                                // Нашли нужный график, выходим из всех циклов
                                break 2;
                            } elseif (count($timeMatches) === 1) {
                                // Если найдено только одно время, используем как начальное
                                $startTime = sprintf('%02d:%02d', (int)$timeMatches[0][1], (int)$timeMatches[0][2]);
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }

    // Генерируем интервалы по 15 минут
    $intervals = [];
    $start = $this->timeToMinutes($startTime);
    $end = $this->timeToMinutes($endTime);

    for ($current = $start; $current < $end; $current += 15) {
        $time1 = $this->minutesToTime($current);
        $time2 = $this->minutesToTime($current + 15);
        $intervals[] = "{$time1}-{$time2}";
    }

    return $intervals;
}

/**
 * Конвертировать время в минуты с начала дня
 */
private function timeToMinutes(string $time): int
{
    [$hours, $minutes] = explode(':', $time);
    return (int)$hours * 60 + (int)$minutes;
}

/**
 * Конвертировать минуты в формат HH:MM
 */
private function minutesToTime(int $minutes): string
{
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Сохранение данных формы в сессию
 */
public function saveFormData(Request $request)
{
    $data = [];

    // Контактные данные
    if ($request->has('contact_name')) {
        $data['contact_name'] = $request->input('contact_name');
    }
    if ($request->has('contact_phone')) {
        $data['contact_phone'] = $request->input('contact_phone');
    }
    if ($request->has('contact_email')) {
        $data['contact_email'] = $request->input('contact_email');
    }

    // Способ получения и адрес
    if ($request->has('shipping_method')) {
        $data['shipping_method'] = $request->input('shipping_method');
    }
    if ($request->has('selected_address_id')) {
        $data['selected_address_id'] = $request->input('selected_address_id');
    }
    if ($request->has('use_new_address')) {
        $data['use_new_address'] = $request->boolean('use_new_address');
    }

    // Данные нового адреса
    if ($request->has('addr_street')) {
        $data['addr_street'] = $request->input('addr_street');
    }
    if ($request->has('addr_house')) {
        $data['addr_house'] = $request->input('addr_house');
    }
    if ($request->has('addr_apartment')) {
        $data['addr_apartment'] = $request->input('addr_apartment');
    }
    if ($request->has('addr_intercom')) {
        $data['addr_intercom'] = $request->input('addr_intercom');
    }
    if ($request->has('addr_floor')) {
        $data['addr_floor'] = $request->input('addr_floor');
    }
    if ($request->has('addr_porch')) {
        $data['addr_porch'] = $request->input('addr_porch');
    }
    if ($request->has('addr_comment')) {
        $data['addr_comment'] = $request->input('addr_comment');
    }
    if ($request->has('addr_is_private_house')) {
        $data['addr_is_private_house'] = $request->boolean('addr_is_private_house');
    }
    if ($request->has('addr_type')) {
        $data['addr_type'] = $request->input('addr_type');
    }
    if ($request->has('addr_lat')) {
        $data['addr_lat'] = $request->input('addr_lat');
    }
    if ($request->has('addr_lng')) {
        $data['addr_lng'] = $request->input('addr_lng');
    }
    if ($request->has('addr_formatted_address')) {
        $data['addr_formatted_address'] = $request->input('addr_formatted_address');
    }
    if ($request->has('addr_street_place_id')) {
        $data['addr_street_place_id'] = $request->input('addr_street_place_id');
    }
    if ($request->has('delivery_zone')) {
        $data['delivery_zone'] = $request->input('delivery_zone');
    }
    if ($request->has('shipping_price')) {
        $data['shipping_price'] = (float) $request->input('shipping_price');
    }
    // Условия доставки
    if ($request->has('delivery_mode')) {
        $data['delivery_mode'] = $request->input('delivery_mode');
    }
    if ($request->has('delivery_date')) {
        $data['delivery_date'] = $request->input('delivery_date');
    }
    if ($request->has('delivery_time')) {
        $data['delivery_time'] = $request->input('delivery_time');
    }

    // Способ оплаты
    if ($request->has('payment_method')) {
        $data['payment_method'] = $request->input('payment_method');
    }
    if ($request->has('payparts_bank_id')) {
        $data['payparts_bank_id'] = (int) $request->input('payparts_bank_id');
    }
    if ($request->has('payparts_plan_key')) {
        $data['payparts_plan_key'] = (string) $request->input('payparts_plan_key');
    }
    if ($request->has('payparts_financial_phone')) {
        $data['payparts_financial_phone'] = (string) $request->input('payparts_financial_phone');
    }
    if ($request->has('selected_promo')) {
        $data['selected_promo'] = (string) $request->input('selected_promo', 'none');
    }

    if ($request->has('use_bonus')) {
        $data['use_bonus'] = $request->boolean('use_bonus');
    }
    if ($request->has('bonus_amount')) {
        $data['bonus_amount'] = (float) $request->input('bonus_amount');
    }

    // Комментарии
    if ($request->has('comment_kitchen')) {
        $data['comment_kitchen'] = $request->input('comment_kitchen');
    }
    if ($request->has('comment_courier')) {
        $data['comment_courier'] = $request->input('comment_courier');
    }

    // Сохраняем в сессию (объединяем с существующими данными, чтобы не потерять уже сохраненные)
    $existingData = session('checkout.form_data', []);
    $mergedData = array_merge($existingData, $data);
    session(['checkout.form_data' => $mergedData]);

    if (array_key_exists('selected_promo', $data)) {
        session(['checkout.selected_promo' => $data['selected_promo'] ?: 'none']);
    }

    // Обновляем черновик заказа, если он существует
    $client = Auth::user();
    if ($client) {
        $order = Order::where('clients_id', $client->id)
            ->where('status', OrderStatus::Cart)
            ->latest('id')
            ->first();

        if ($order) {
            // Обновляем дату и время доставки в черновике
            $deliveryMode = $mergedData['delivery_mode'] ?? 'asap';
            $deliveryDate = $mergedData['delivery_date'] ?? null;
            $deliveryTimeRaw = $mergedData['delivery_time'] ?? null;

            // Извлекаем первое время из диапазона
            $deliveryTime = $deliveryTimeRaw;
            if ($deliveryTimeRaw && strpos($deliveryTimeRaw, '-') !== false) {
                $deliveryTime = trim(explode('-', $deliveryTimeRaw)[0]);
            }

            // Обновляем дату доставки
            if ($deliveryMode === 'fixed' && $deliveryDate) {
                try {
                    // Пробуем разные форматы даты
                    // Формат от flatpickr altInput: d.m.Y (например, "21.01.2026")
                    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $deliveryDate)) {
                        $date = Carbon::createFromFormat('d.m.Y', $deliveryDate);
                        $order->date_order = $date->toDateString();
                    }
                    // Формат от flatpickr dateFormat: Y-m-d (например, "2026-01-21")
                    elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
                        $date = Carbon::createFromFormat('Y-m-d', $deliveryDate);
                        $order->date_order = $date->toDateString();
                    } else {
                        // Пробуем автоматическое определение формата
                        $date = Carbon::parse($deliveryDate);
                        $order->date_order = $date->toDateString();
                    }
                } catch (\Throwable $e) {
                    // Оставляем текущую дату при ошибке парсинга
                    \Log::warning('Failed to parse delivery date in saveFormData', [
                        'date' => $deliveryDate,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($deliveryMode === 'asap') {
                // Если переключились на "как можно скорее", устанавливаем сегодняшнюю дату
                $order->date_order = now()->toDateString();
            }

            // Обновляем время доставки
            if ($deliveryMode === 'fixed' && $deliveryTime) {
                $order->time_order = $deliveryTime;
            } elseif ($deliveryMode === 'asap') {
                // Если переключились на "как можно скорее", устанавливаем время через час
                $order->time_order = now()->addMinutes(60)->format('H:i');
            }

            // Обновляем режим доставки
            $order->as_soon_possible = $deliveryMode === 'asap';
            // ✅ пишем стоимость доставки в черновик
            if (array_key_exists('shipping_price', $mergedData)) {
                $order->shipping_price = (float) $mergedData['shipping_price'];
            }

            // если самовывоз — доставка 0
            if (($mergedData['shipping_method'] ?? null) === 'pickup') {
                $order->shipping_price = 0;
            }
// ✅ 1) Способ получения (доставка/самовывоз)
            $shippingMethod = $mergedData['shipping_method'] ?? ($order->self_pickup ? 'pickup' : 'delivery');
            $order->shipping_method = $shippingMethod;

// если у тебя в заказе есть поле self_pickup:
            $order->self_pickup = ($shippingMethod === 'pickup');

// ✅ 2) Стоимость доставки
            if (isset($mergedData['shipping_price'])) {
                $order->shipping_price = (float) $mergedData['shipping_price'];
            }

// ✅ 3) Выбранный сохранённый адрес
            $useNew = !empty($mergedData['use_new_address']);

            if ($shippingMethod === 'pickup') {
                // самовывоз — адрес не нужен, доставка 0
                $order->client_address_id = null;
                $order->shipping_price = 0;
            } elseif (! $useNew) {
                // Новый адрес создаёт submit(); автосохранение не должно стереть его запоздавшим запросом.
                $addrId = $mergedData['selected_address_id'] ?? null;
                $order->client_address_id = $addrId ? (int) $addrId : null;
            }

            if ($shippingMethod !== 'pickup' && ! $useNew && empty($mergedData['selected_address_id'])) {
                $order->shipping_price = 0;
            }

            $order->shipping_total = $shippingMethod === 'pickup'
                ? 0
                : max(0, (float) ($order->shipping_price ?? 0));

            $selection = (string) ($mergedData['selected_promo'] ?? session('checkout.selected_promo', 'none'));
            $pricing = app(OrderPricing::class);

            $commentKitchen = trim((string) ($mergedData['comment_kitchen'] ?? ''));
            $commentCourier = trim((string) ($mergedData['comment_courier'] ?? ''));
            $notesFromCourier = $commentCourier !== '' ? 'Курьер: ' . $commentCourier : null;

            $order->kitchen_note = $commentKitchen !== '' ? $commentKitchen : null;
            $order->courier_comment = null;
            $order->notes = $notesFromCourier;

            $order->adjustments()->whereIn('type', ['fixed', 'time'])->delete();
            if ($selection !== '' && $selection !== 'none') {
                [$kind, $id] = explode(':', $selection) + [null, null];
                $id = (int) $id;

                if ($kind === 'fixed') {
                    $pricing->applyFixedExclusive($order, $id, 'single');
                } elseif ($kind === 'time') {
                    $pricing->applyTimeExclusive($order, $id, 'single');
                } else {
                    $pricing->recalc($order);
                }
            } else {
                $pricing->recalc($order);
            }

            $promoDiscount = abs((float) $order->adjustments()
                ->whereNull('shop_order_item_id')
                ->whereIn('type', ['fixed', 'time'])
                ->sum('amount'));
            session(['checkout.promo_discount' => $promoDiscount]);

            $useBonus = !empty($mergedData['use_bonus']);
            $bonusAmount = $useBonus ? max(0, (float) ($mergedData['bonus_amount'] ?? 0)) : 0.0;
            $order->sale_sum = $bonusAmount;
            $this->recalculateOrderShipping($order);
            $order->grand_total = $this->calculatePayableTotal($order);

            $order->save();
        }
    }

    return response()->json(['ok' => true]);
}
public function updatePromo(Request $request)
{
    $client    = auth()->user();
    $selection = (string) $request->input('promo', 'none');

    // если гость — не даём применять акцию, только после логина
    if (! $client) {
        session(['checkout.selected_promo' => 'none']);
        session(['checkout.promo_discount' => 0]);

        return response()->json([
            'ok'            => false,
            'requires_auth' => true,
            'message'       => 'Щоб застосувати акцію, увійдіть або зареєструйтесь.',
        ]);
    }

    // запомним выбор в сессию
    session(['checkout.selected_promo' => $selection]);

    // 1) находим Cart-заказ клиента
    $order = Order::where('clients_id', $client->id)
        ->where('status', OrderStatus::Cart)
        ->latest('id')
        ->first();

    if (! $order) {
        // нет черновика — просто вернём сумму корзины
        $info      = $this->cart->info();
        $baseTotal = (float) ($info['total_price'] ?? 0);

        session(['checkout.promo_discount' => 0]);

        return response()->json([
            'ok'        => true,
            'selection' => $selection,
            'discount'  => 0,
            'total'     => $baseTotal,
            'bonus_earn' => $this->loyalty->previewEarnForCart($baseTotal, 0),
            'discount_formatted' => number_format(0, 2, ',', ' ') . ' грн',
            'total_formatted'    => number_format($baseTotal, 2, ',', ' ') . ' грн',
            'total_uah'          => (int) floor($baseTotal),
            'total_uah_formatted'=> number_format((int) floor($baseTotal), 0, ',', ' '),
            'total_kop'          => sprintf('%02d', (int) round(($baseTotal - floor($baseTotal)) * 100)),
        ]);
    }

    // 2) Применяем выбранную акцию ЕДИНОЙ логикой (как в админке)
    $pricing = app(\App\Services\OrderPricing::class);

    // чистим прошлые скидки fixed/time
    $order->adjustments()->whereIn('type', ['fixed', 'time'])->delete();

    if ($selection !== 'none') {
        [$kind, $id] = explode(':', $selection) + [null, null];
        $id = (int) $id;

        if ($kind === 'fixed') {
            $pricing->applyFixedExclusive($order, $id, 'single');
        } elseif ($kind === 'time') {
            $pricing->applyTimeExclusive($order, $id, 'single');
        } else {
            $pricing->recalc($order);
        }
    } else {
        $pricing->recalc($order);
    }

    $sessionData = session('checkout.form_data', []);

    $shippingMethod = (string) ($sessionData['shipping_method'] ?? ($order->self_pickup ? 'pickup' : 'delivery'));
    $order->shipping_method = $shippingMethod;
    $order->self_pickup = $shippingMethod === 'pickup';

    $selectedAddressId = (int) ($sessionData['selected_address_id'] ?? 0);
    if (empty($sessionData['use_new_address']) && $selectedAddressId > 0) {
        $order->client_address_id = $selectedAddressId;
    }

    $useBonus = !empty($sessionData['use_bonus']);
    $order->sale_sum = $useBonus ? max(0, (float) ($sessionData['bonus_amount'] ?? 0)) : 0.0;
    $this->recalculateOrderShipping($order);
    $order->grand_total = $this->calculatePayableTotal($order);
    $order->save();

    // 3) Берём результат из БД (adjustments уже записаны)
    $discount = abs((float) $order->adjustments()
        ->whereNull('shop_order_item_id')
        ->whereIn('type', ['fixed', 'time'])
        ->sum('amount')); // amount отрицательный, поэтому abs()

    $total = (float) ($order->grand_total ?? 0);
    $itemsTotal = (float) ($order->total_price ?? 0);
    $bonusEarn = $this->loyalty->previewEarnForCart($itemsTotal, $discount, (float) ($order->sale_sum ?? 0));
    $shipping = (float) ($order->shipping_total ?? $order->shipping_price ?? 0);

    session(['checkout.promo_discount' => $discount]);

    $uah = (int) floor($total);
    $kop = (int) round(($total - $uah) * 100);

    return response()->json([
        'ok'        => true,
        'selection' => $selection,

        'discount'  => $discount,
        'total'     => $total,
        'shipping'  => $shipping,
        'bonus_earn' => $bonusEarn,

        'discount_formatted' => number_format($discount, 2, ',', ' ') . ' грн',
        'total_formatted'    => number_format($total, 2, ',', ' ') . ' грн',

        'total_uah'          => $uah,
        'total_uah_formatted'=> number_format($uah, 0, ',', ' '),
        'total_kop'          => sprintf('%02d', $kop),
    ]);
}

public function applyCoupon(Request $request)
{
    try {
        $code = trim((string) $request->input('coupon', ''));

        if ($code === '') {
            return response()->json([
                'ok'   => false,
                'mess' => 'Введите промокод',
            ]);
        }

        // 1) ищем активный промокод
        $promo = PromoCode::query()
            ->active()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if (! $promo) {
            return response()->json([
                'ok'   => false,
                'mess' => st('checkout.promo.not_found_or_inactive', 'Промокод не найден или не активен'),
            ]);
        }

        $client   = auth()->user();
        $clientId = $client?->id;

        // 2) лимиты/доступность для клиента
        if (! $promo->canApplyForClient($clientId)) {
            return response()->json([
                'ok'   => false,
                'mess' => st('checkout.promo.unavailable_now', 'Этот промокод сейчас нельзя применить'),
            ]);
        }

        // 3) берём текущие позиции из корзины
        $cartItems = $this->cart->items();
        if (empty($cartItems)) {
            return response()->json([
                'ok'   => false,
                'mess' => st('checkout.promo.cart_empty', 'Корзина пуста'),
            ]);
        }

        // 4) собираем ВИРТУАЛЬНЫЙ заказ:
        // превращаем элементы корзины в OrderItem-модели с привязанным Product
        $items = collect($cartItems)->map(function ($row) {
            $item = new OrderItem();

            if (is_array($row)) {
                $product = $row['product'] ?? null;

                $item->qty        = $row['qty'] ?? $row['quantity'] ?? 1;
                $item->unit_price = $row['unit_price'] ?? $row['price'] ?? 0;
                $item->meta       = $row['meta'] ?? [];
                $item->product_id = $row['product_id'] ?? ($product?->id);
            } else {
                // объект из CartService
                $product = $row->product ?? null;

                $item->qty        = $row->qty ?? 1;
                $item->unit_price = $row->unit_price ?? $row->price ?? 0;
                $item->meta       = $row->meta ?? [];
                $item->product_id = $row->product_id ?? ($product?->id);
            }

            if ($product instanceof Product) {
                $item->setRelation('product', $product);
            }

            return $item;
        });

        $order = new Order();
        $order->setRelation('items', $items);

        // 5) считаем скидку через уже готовый метод
        $amount = (float) $promo->calculateAmountForOrder($order);

        if ($amount >= 0.0) {
            return response()->json([
                'ok'   => false,
                'mess' => st('checkout.promo.no_discount_for_items', 'Промокод не даёт скидки для текущих товаров'),
            ]);
        }

        return response()->json([
            'ok'       => true,
            'code'     => $promo->code,
            'discount' => round(abs($amount), 2),
            'message'  => st('checkout.promo.applied', 'Промокод застосовано'),
        ]);

    } catch (\Throwable $e) {
        \Log::error('applyCoupon error: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'ok'   => false,
            'mess' => 'Внутренняя ошибка: '.$e->getMessage(),
        ]);
    }
}





public function submit(Request $request)
{
    $items = $this->cart->items();
    $info = $this->cart->info();

    if ($this->isCartEmpty($items, $info)) {
        $this->resetCheckoutDynamicState();
        $locale = app()->getLocale();
        $routeName = in_array($locale, ['ru', 'en'], true)
            ? 'localized.home'
            : 'home';
        $routeParams = in_array($locale, ['ru', 'en'], true)
            ? ['locale' => $locale]
            : [];

        return redirect()->route($routeName, $routeParams);
    }

    $client = auth()->user();
    $couponCode = trim((string) $request->input('coupon', ''));
    // 1. Базовая валидация
    $validated = $request->validate([
        'contact_name'     => 'required|string|max:255',
        'contact_phone'    => 'required|string|max:50',
        'contact_email'    => 'nullable|email|max:255',

        'shipping_method'  => 'required|in:delivery,pickup',
        'delivery_mode'    => 'required|in:asap,fixed',
        'delivery_time'    => 'nullable|string|max:20',

        'payment_method'   => 'required|in:liqpay,card_on_delivery,cash,invoice,payparts',

        'agree'            => 'accepted',
    ]);

    if ($request->input('payment_method') === 'liqpay') {
        $validated['contact_email'] = trim((string) ($request->input('contact_email') ?? ''));
    }

    if ($request->input('payment_method') === 'payparts') {
        $request->validate([
            'payparts_bank_id' => 'required|integer|exists:bs_payparts_banks,id',
            'payparts_plan_key' => 'required|string|max:80',
            'payparts_financial_phone' => ['required', 'string', 'regex:/^380\d{9}$/'],
        ], [
            'payparts_financial_phone.required' => st('cart.payment.payparts_financial_phone_required', 'Поле обовʼязкове'),
            'payparts_financial_phone.regex' => st('cart.payment.payparts_financial_phone_invalid', 'Введіть повний номер телефону'),
        ]);
    }

    $shippingMethod = $validated['shipping_method'];

    // 2. Условная валидация: дата/время обязательны только для доставки в режиме "fixed"
    $deliveryMode = $request->input('delivery_mode', 'asap');
    if ($shippingMethod === 'delivery' && $deliveryMode === 'fixed') {
        $request->validate([
            'delivery_date' => 'required|string|max:50',
            'delivery_time' => 'required|string|max:20',
        ], [
            'delivery_date.required' => st('cart.delivery.date_required', 'Оберіть дату доставки'),
            'delivery_time.required' => st('cart.delivery.time_required', 'Оберіть час доставки'),
        ]);
    }

    // 2. Адрес: существующий или новый (только для доставки)
    $useNew = false;
    $addressId = null;
    if ($shippingMethod === 'delivery') {
        $useNew = $request->boolean('use_new_address')
            || ! $client
            || ($client && ! $client->addresses()->exists());

        if ($useNew) {
            $addr = $request->validate([
                'addr.street'           => 'required|string|max:255',
                'addr.house'            => 'required|string|max:50',
                'addr.apartment'        => 'nullable|string|max:50',
                'addr.intercom'         => 'nullable|string|max:50',
                'addr.floor'            => 'nullable|string|max:20',
                'addr.porch'            => 'nullable|string|max:20',
                'addr.comment'          => 'nullable|string|max:500',
                'addr.is_private_house' => 'nullable|boolean',
                'addr.type'             => 'nullable|string|in:home,work,friends',
                'addr.lat'              => 'nullable|numeric',
                'addr.lng'              => 'nullable|numeric',
                'addr.formatted_address'=> 'nullable|string|max:255',
                'addr.street_place_id'   => 'nullable|string|max:255',
            ]);

            $formattedAddress = trim((string) ($addr['addr']['formatted_address'] ?? ''));
            $streetValue = trim((string) $addr['addr']['street']);
            if ($streetValue === '') {
                $streetValue = $formattedAddress;
            }

            $addrData = [
                'client_id'        => $client?->id,
                'street'           => $streetValue,
                'house'            => $addr['addr']['house'],
                'apartment'        => $addr['addr']['apartment'] ?? null,
                'intercom'         => $addr['addr']['intercom'] ?? null,
                'floor'            => $addr['addr']['floor'] ?? null,
                'entrance'         => $addr['addr']['porch'] ?? null,
                'note'             => $addr['addr']['comment'] ?? null,
                'is_private_house' => !empty($addr['addr']['is_private_house']),
                'type'             => $addr['addr']['type'] ?? null,
                'city'             => 'Київ',
                // координаты из формы checkout (заполняются Google Autocomplete)
                'latitude'         => $addr['addr']['lat'] ?? null,
                'longitude'        => $addr['addr']['lng'] ?? null,
                'formatted_address'=> $formattedAddress !== '' ? $formattedAddress : null,
                'street_place_id'   => $addr['addr']['street_place_id'] ?? null,
            ];

            $address   = ClientAddress::create($addrData);
            $addressId = $address->id;
        } else {
            $addressId = $request->input('selected_address_id');

            if ($client) {
                $address = $client->addresses()
                    ->whereKey($addressId)
                    ->first();
            } else {
                $address = ClientAddress::find($addressId);
            }

            if (! $address) {
                return back()
                    ->withErrors(['selected_address_id' => 'Оберіть адресу доставки або введіть нову.'])
                    ->withInput();
            }

            $addressId       = $address->id;
        }
    }

    // 3. Доставка (asap / fixed)

    $deliveryMode   = $request->input('delivery_mode', 'asap');
    $deliveryDate   = $request->input('delivery_date');
    $deliveryTimeRaw = $request->input('delivery_time'); // Диапазон типа "12:00-12:15"

    // Извлекаем первое время из диапазона для сохранения в базу
    // Если формат "12:00-12:15", берем "12:00"
    $deliveryTime = $deliveryTimeRaw;
    if ($deliveryTimeRaw && strpos($deliveryTimeRaw, '-') !== false) {
        $deliveryTime = trim(explode('-', $deliveryTimeRaw)[0]);
    }

    // Валидация даты и времени для режима "fixed" уже выполнена выше

    // 4. Способ оплаты -> enum
    $paymentEnum = match ($request->input('payment_method')) {
        'liqpay'          => PaymentMethodEnum::LIQPAY,
        'card_on_delivery'=> PaymentMethodEnum::POS,
        'cash'            => PaymentMethodEnum::CASH,
        'invoice'         => PaymentMethodEnum::INVOICE,
        'payparts'        => PaymentMethodEnum::PAYPARTS,
        default           => PaymentMethodEnum::CASH,
    };

    // 5. Контактные данные можно сразу сохранить в клиента
    if ($client) {
        $client->fill([
            'name'  => $validated['contact_name'],
            'phone' => $validated['contact_phone'],
            'email' => $validated['contact_email'] ?? $client->email,
        ])->saveQuietly();
    }

    // 6. Ищем черновик заказа клиента, если есть
    $order = null;
    if ($client) {
        // Проверяем, что клиент существует в базе данных
        $clientExists = \App\Models\Shop\Client::where('id', $client->id)->exists();

        if ($clientExists) {
            $order = Order::where('clients_id', $client->id)
                ->where('status', OrderStatus::Cart)
                ->latest('id')
                ->first();
        } else {
            \Log::warning('Client not found when searching for draft order', [
                'client_id' => $client->id,
            ]);
        }
    }

    // Если заказа нет, создаем новый
    if (!$order) {
        $order = new Order();
        $order->status = OrderStatus::Cart;
        $order->total_price = 0;
        $order->currency = 'UAH';

        // Устанавливаем clients_id только если клиент существует
        if ($client) {
            $clientExists = \App\Models\Shop\Client::where('id', $client->id)->exists();
            $order->clients_id = $clientExists ? $client->id : null;
        } else {
            $order->clients_id = null; // Гостевой заказ
        }

        $order->save();
    }

    $cartInfo = $this->cart->info();

    // === 6.1. БОНУСЫ: считаем баланс, лимит и желаемое списание ===

    // Сколько сейчас товаров на сумму
    $itemsTotal = (float)($cartInfo['total_price'] ?? 0);
    // Пока других скидок нет — 0. Если потом появятся промокоды,
    // сюда можно подставить сумму скидки.
    $discountBase = 0.0;

    // Для лояльности берём client_id + телефон.
    $loyaltyClientId = $client?->id;
    $loyaltyPhone    = $client?->phone ?? $validated['contact_phone'];

    $balance = $this->loyalty->getBalance($loyaltyClientId, $loyaltyPhone);
    $limit   = $this->loyalty->getBonusLimitForOrder($itemsTotal, $discountBase, $balance);

    $useBonus       = $request->boolean('use_bonus');
    $requestedBonus = $useBonus ? (float)$request->input('bonus_amount', 0) : 0.0;
    $requestedBonus = max(0, min($requestedBonus, $limit)); // защита от «накрутки» из формы

    // Комментарии
    $commentKitchen = trim((string)$request->input('comment_kitchen', ''));
    $commentCourier = trim((string)$request->input('comment_courier', ''));
    $notesFromCourier = $commentCourier !== '' ? 'Курьер: ' . $commentCourier : null;
    $order->self_pickup = $shippingMethod === 'pickup' ? 1 : 0;

    // 7. Заполняем заказ (пока без учёта бонусов, только базовые суммы)
    $order->fill([
        'short_name'        => $validated['contact_name'],
        'client_address_id' => $addressId,
        'shipping_method'   => $shippingMethod,
        'self_pickup'       => $shippingMethod === 'pickup',
        'as_soon_possible'  => $deliveryMode === 'asap',

        'payment'           => $paymentEnum,
        'payparts_bank_id'  => $paymentEnum === PaymentMethodEnum::PAYPARTS
            ? (int) $request->input('payparts_bank_id')
            : null,

        'notes'             => $notesFromCourier,
        'kitchen_note'      => $commentKitchen !== '' ? $commentKitchen : null,
        'courier_comment'   => null,

        'total_price'       => $itemsTotal,
        'shipping_price'    => $shippingMethod === 'pickup' ? 0 : ($order->shipping_price ?? 0),
        'shipping_total'    => $shippingMethod === 'pickup' ? 0 : ($order->shipping_price ?? 0),
    ]);

    $order->dat        = now()->toDateString();       // yyyy-mm-dd
    $order->time_start = now()->format('H:i');        // если поле у тебя есть

    // === ДАТА ДОСТАВКИ ===
    if ($deliveryMode === 'fixed' && $deliveryDate) {
        try {
            // Поддерживаем оба формата, т.к. flatpickr отправляет Y-m-d,
            // а altInput может давать d.m.Y.
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $deliveryDate)) {
                $date = Carbon::createFromFormat('d.m.Y', $deliveryDate);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
                $date = Carbon::createFromFormat('Y-m-d', $deliveryDate);
            } else {
                $date = Carbon::parse($deliveryDate);
            }

            $order->date_order = $date->toDateString();
        } catch (\Throwable $e) {
            \Log::warning('Failed to parse delivery date in submit', [
                'date' => $deliveryDate,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            // Не перетираем ранее установленную дату в черновике,
            // но если её нет — ставим сегодня.
            if (empty($order->date_order)) {
                $order->date_order = now()->toDateString();
            }
        }
    } else {
        // если пользователь не выбрал дату — авто: сегодня
        $order->date_order = now()->toDateString();
    }

    // === ВРЕМЯ ДОСТАВКИ ===
    if ($deliveryMode === 'fixed' && $deliveryTime) {
        // ожидаем формат "HH:MM" от клиента
        $order->time_order = $deliveryTime;
    } else {
        if (empty($order->time_order)) {
            $order->time_order = now()->addMinutes(60)->format('H:i');
        }
    }

    if (in_array($paymentEnum, [PaymentMethodEnum::LIQPAY, PaymentMethodEnum::PAYPARTS], true)) {
        // можно оставить Cart как статус ожидания оплаты,
        // либо сделать отдельное значение в enum (например, AwaitingPayment)
        $order->status = OrderStatus::Cart;
    } else {
        $order->status = OrderStatus::New;
    }

    $order->save();

    // 8. Для гостя переносим позиции из сессии в заказ
    if (auth()->guest() && method_exists($this->cart, 'storeItemsInOrder')) {
        $this->cart->storeItemsInOrder($order);
    }

    // Пересчёт суммы по связям (items + modifiers)
    $order->recalculateTotalPrice();
// === 8.1 Применяем выбранную акцию (fixed/time) через общий сервис
    $selection = $request->input('selected_promo', session('checkout.selected_promo', 'none'));

    $pricing = app(\App\Services\OrderPricing::class);

// чистим прошлые скидки fixed/time
    $order->adjustments()->whereIn('type', ['fixed', 'time'])->delete();

    if ($selection && $selection !== 'none') {
        [$kind, $id] = explode(':', $selection) + [null, null];
        $id = (int) $id;

        if ($kind === 'fixed') {
            $pricing->applyFixedExclusive($order, $id, 'single');
        } elseif ($kind === 'time') {
            $pricing->applyTimeExclusive($order, $id, 'single');
        } else {
            $pricing->recalc($order);
        }
    } else {
        $pricing->recalc($order);
    }


// === 8.1 Применяем промокод (если был введён) ===
    if ($couponCode !== '') {
        try {
            $promo = PromoCode::query()
                ->active()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($couponCode)])
                ->first();

            if ($promo && $promo->canApplyForClient($client?->id)) {
                // подгружаем нужные связи для расчёта
                $order->loadMissing(['items.product.categories']);
                if (method_exists(Product::class, 'attributeValues')) {
                    $order->loadMissing(['items.product.attributeValues']);
                }

                // считаем скидку для ЭТОГО заказа
                $amount = (float) $promo->calculateAmountForOrder($order); // ОТРИЦАТЕЛЬНОЕ число

                if ($amount < 0) {
                    $discount = abs($amount);

                    // на всякий случай удаляем все старые купонные скидки
                    $order->adjustments()
                        ->where('type', 'coupon')
                        ->delete();

                    // создаём одну запись в bs_shop_order_adjustments
                    OrderAdjustment::create([
                        'shop_order_id' => $order->id,
                        'type'          => 'coupon',
                        'label'         => 'Промокод ' . $promo->code,
                        'amount'        => $amount, // отрицательное значение
                        'meta'          => [
                            'code'          => $promo->code,
                            'promo_code_id' => $promo->id,
                        ],
                    ]);

                    // если у заказа есть поле promo_code — сохраним код
                    if ($order->isFillable('promo_code')) {
                        $order->promo_code = $promo->code;
                    }
                    $order->save();

                    // отмечаем использование промокода (по order_id будет только одна запись)
                    $promo->markUsed($client?->id, $order->id);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Checkout promo apply error: '.$e->getMessage(), [
                'order_id' => $order->id,
                'coupon'   => $couponCode,
            ]);
        }
    }

// === 9. Реальное списание бонусов по заказу ===
    $used = 0.0;
    if ($requestedBonus > 0) {
        $used = $this->loyalty->spendOnOrder($order, $requestedBonus);
    }

    $spentBonuses = $used > 0 ? $used : $order->resolveSpentBonuses();

    $order->sale_sum = max(0, round($spentBonuses, 2));
    $order->total_price_sale = max(0, round((float) $order->total_price - (float) $order->sale_sum, 2));
    $this->recalculateOrderShipping($order);
    $order->save();

    // === 9.1 Финальный пересчёт grand_total для оплаты / Filament ===
    // Считаем от фактической суммы товаров + adjustments,
    // затем отдельно вычитаем бонусы (sale_sum), и добавляем доставку.
    $order->grand_total = $this->calculatePayableTotal($order);
    $order->save();

    // 10. Отправляем уведомление на почту:
    //     - для LiqPay письмо уходит ТОЛЬКО после успешного callback'а
    //     - для остальных способов оплаты шлём сразу после оформления.
    if (! in_array($paymentEnum, [PaymentMethodEnum::LIQPAY, PaymentMethodEnum::PAYPARTS], true)) {
        try {
            $order->load([
                'items.product.parent.productCharacteristicValues.characteristic.svgImage',
                'items.product.productCharacteristicValues.characteristic.svgImage',
                'items.product.productCharacteristicValues.characteristicValue',
                'adjustments',
                'clientAddress',
                'clients'
            ]);
            $notificationEmails = config('notifications.order_notification_email', []);
            // Если это строка (старый формат), преобразуем в массив
            if (is_string($notificationEmails)) {
                $notificationEmails = array_filter(array_map('trim', explode(',', $notificationEmails)));
            }
            // Если массив пустой, используем fallback
            if (empty($notificationEmails)) {
                $notificationEmails = ['info@3piroga.ua'];
            }

            if (!empty($notificationEmails)) {
                \Log::info('Sending order notification email', [
                    'order_id' => $order->id,
                    'emails' => $notificationEmails,
                    'mail_driver' => config('mail.default'),
                ]);
                Mail::to($notificationEmails)->send(new OrderNotificationMail($order));
                \Log::info('Order notification email sent successfully', [
                    'order_id' => $order->id,
                    'emails' => $notificationEmails,
                ]);
            } else {
                \Log::warning('Order notification email not configured', ['order_id' => $order->id]);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send order notification email: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'email' => $notificationEmail ?? 'not configured',
                'mail_driver' => config('mail.default'),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    if ($paymentEnum === PaymentMethodEnum::LIQPAY) {
        // своя страница с кнопкой LiqPay
        $locale = app()->getLocale();
        $routeName = in_array($locale, ['ru', 'en'], true)
            ? 'localized.checkout.pay.liqpay'
            : 'checkout.pay.liqpay';
        $routeParams = in_array($locale, ['ru', 'en'], true)
            ? ['locale' => $locale, 'order' => $order]
            : ['order' => $order];

        return redirect()->route($routeName, $routeParams);
    }

    if ($paymentEnum === PaymentMethodEnum::PAYPARTS) {
        $bank = PaypartsBank::query()
            ->active()
            ->visibleForClient($client)
            ->find((int) $request->input('payparts_bank_id'));

        if (! $bank) {
            $order->payparts_status = 'payment_failed';
            $order->save();

            return back()
                ->withInput()
                ->with('error', st('cart.payment.payparts_bank_unavailable', 'Обраний банк зараз недоступний.'));
        }

        $plans = collect($bank->plansForAmount((float) $order->grand_total));
        $plan = $plans->firstWhere('key', (string) $request->input('payparts_plan_key'));

        if (! $plan) {
            $order->payparts_status = 'payment_failed';
            $order->save();

            return back()
                ->withInput()
                ->with('error', st('cart.payment.payparts_plan_unavailable', 'Обрані умови оплати частинами недоступні для цієї суми.'));
        }

        $order->payparts_status = 'pending_payment';
        $order->save();

        session([
            'checkout.payparts.' . $order->id => [
                'plan_key' => (string) $request->input('payparts_plan_key'),
                'contact_phone' => (string) $request->input('payparts_financial_phone', $validated['contact_phone']),
                'contact_email' => (string) ($validated['contact_email'] ?? ''),
            ],
        ]);

        $locale = app()->getLocale();
        $routeName = in_array($locale, ['ru', 'en'], true)
            ? 'localized.checkout.pay.payparts'
            : 'checkout.pay.payparts';
        $routeParams = in_array($locale, ['ru', 'en'], true)
            ? ['locale' => $locale, 'order' => $order]
            : ['order' => $order];

        return redirect()->route($routeName, $routeParams);
    }

// 11. Для не-LiqPay очищаем корзину и форму после успешного оформления
    if (method_exists($this->cart, 'clearAfterCheckout')) {
        $this->cart->clearAfterCheckout();
    }
    session()->forget('checkout.selected_promo');
    session()->forget('checkout.promo_discount');
    session()->forget('checkout.cart_signature');
    session()->forget('checkout.form_data');

    $locale = app()->getLocale();
    $routeName = in_array($locale, ['ru', 'en'], true)
        ? 'localized.checkout.success'
        : 'checkout.success';
    $routeParams = in_array($locale, ['ru', 'en'], true)
        ? ['locale' => $locale, 'order' => $order]
        : ['order' => $order];

    $this->rememberRecentCheckoutOrder($order);

    return redirect()->route($routeName, $routeParams);

}

public function success($localeOrOrder, ?Order $order = null)
{
    // Support both routes:
    // - /checkout/success/{order} => success(Order $order)
    // - /{locale}/checkout/success/{order} => success(string $locale, Order $order)
    $locale = null;
    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } else {
        $locale = is_string($localeOrOrder) ? $localeOrOrder : null;
    }

    if (! $order instanceof Order) {
        abort(404);
    }

    // защита от чужих заказов
    if (! $this->canAccessCustomerOrder($order)) {
        abort(403);
    }
  //  dd($order);
    // сразу подгружаем items + product
   // $order->load('items.product');

   // $items = $order->items;
 //   dd($items);
    // подгружаем позиции и связанные товары
    //$items = $order->items()->with('product')->get();
    $order->load(['items.product.parent']);   // имя связи parent можно поменять, см. ниже

    // Проверяем, рабочее ли сейчас время (для отображения разного текста)
    $isWorkingHours = $this->isWorkingHours();

    // Получаем номер заказа
    $orderNumber = $order->number ?? str_pad($order->id, 5, '0', STR_PAD_LEFT);

    $items = $order->items;
  //  $info   = $this->cart->info();

   // dd($items);
    return view(front_view('checkout.success'), compact('order', 'items', 'isWorkingHours', 'orderNumber'));
}

/**
 * Отправка заказа на email клиенту
 */
public function sendOrderToEmail(Request $request, string|Order $localeOrOrder, ?Order $order = null)
{
    $mailLocale = app()->getLocale();

    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } elseif (in_array($localeOrOrder, ['uk', 'ru', 'en'], true)) {
        $mailLocale = $localeOrOrder;
    }

    if (! $order instanceof Order) {
        abort(404);
    }

    // Защита от чужих заказов
    if (! $this->canAccessCustomerOrder($order)) {
        abort(403);
    }

    // Загружаем связь clients если не загружена
    if (!$order->relationLoaded('clients')) {
        $order->load('clients');
    }

    // Получаем email клиента
    $clientEmail = null;
    if ($order->clients && !empty($order->clients->email)) {
        $clientEmail = $order->clients->email;
    } elseif ($request->has('email') && filter_var($request->input('email'), FILTER_VALIDATE_EMAIL)) {
        $clientEmail = $request->input('email');
    } elseif (auth()->check() && auth()->user()->email) {
        // Если пользователь авторизован, используем его email
        $clientEmail = auth()->user()->email;
    }

    if (!$clientEmail) {
        return response()->json([
            'success' => false,
            'message' => st('order.email.no_email', 'Email не указан'),
        ], 400);
    }

    $originalLocale = app()->getLocale();

    try {
        app()->setLocale($mailLocale);

        \Log::info('Sending order email to client', [
            'order_id' => $order->id,
            'email' => $clientEmail,
            'locale' => app()->getLocale(),
            'mail_driver' => config('mail.default'),
        ]);

        Mail::to($clientEmail)
            ->locale($mailLocale)
            ->send(new OrderClientMail($order, $mailLocale));

        app()->setLocale($originalLocale);

        \Log::info('Order email sent to client successfully', [
            'order_id' => $order->id,
            'email' => $clientEmail,
        ]);

        return response()->json([
            'success' => true,
            'message' => st('order.email.sent_success', 'Замовлення відправлено на email'),
        ]);
    } catch (\Throwable $e) {
        app()->setLocale($originalLocale);

        \Log::error('Failed to send order email to client: ' . $e->getMessage(), [
            'order_id' => $order->id,
            'email' => $clientEmail,
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => st('order.email.sent_error', 'Помилка відправки email'),
        ], 500);
    }
}

/**
 * Проверяет, рабочее ли сейчас время (на основе графика доставки)
 */
private function isWorkingHours(): bool
{
    $locations = Location::query()
        ->where('is_active', 1)
        ->orderBy('sort')
        ->get();

    if ($locations->isEmpty()) {
        // Если нет локаций, считаем рабочее время с 08:30 до 21:00
        $now = now();
        $currentTime = $now->format('H:i');
        return $currentTime >= '08:30' && $currentTime <= '21:00';
    }

    foreach ($locations as $location) {
        $schedule = $location->schedule ?? null;

        if (is_array($schedule) && !empty($schedule)) {
            foreach ($schedule as $scheduleItem) {
                $slug = trim($scheduleItem['slug'] ?? '');
                if ($slug !== 'delivery') {
                    continue;
                }

                $isActive = $scheduleItem['is_active'] ?? ($scheduleItem['data']['is_active'] ?? true);
                if ($isActive === false) {
                    continue;
                }

                $timeData = $scheduleItem['time'] ?? $scheduleItem['data']['time'] ?? null;
                $timeStr = null;

                if (is_array($timeData)) {
                    if (isset($timeData['uk']['uk']) && is_string($timeData['uk']['uk'])) {
                        $timeStr = $timeData['uk']['uk'];
                    } elseif (isset($timeData['uk']) && is_string($timeData['uk'])) {
                        $timeStr = $timeData['uk'];
                    }
                } elseif (is_string($timeData)) {
                    $timeStr = $timeData;
                }

                if ($timeStr) {
                    // Парсим время "з 09:00 до 21:00"
                    if (preg_match_all('/(\d{1,2}):(\d{2})/u', $timeStr, $timeMatches, PREG_SET_ORDER)) {
                        if (count($timeMatches) >= 2) {
                            $startTime = sprintf('%02d:%02d', (int)$timeMatches[0][1], (int)$timeMatches[0][2]);
                            $endTime = sprintf('%02d:%02d', (int)$timeMatches[1][1], (int)$timeMatches[1][2]);

                            $now = now();
                            $currentTime = $now->format('H:i');

                            return $currentTime >= $startTime && $currentTime <= $endTime;
                        }
                    }
                }
                break;
            }
        }
    }

    // Если не нашли график, считаем рабочее время с 08:30 до 21:00
    $now = now();
    $currentTime = $now->format('H:i');
    return $currentTime >= '08:30' && $currentTime <= '21:00';
}


public function payLiqPay($localeOrOrder, ?Order $order = null)
{
    // Support both routes:
    // - /checkout/{order}/pay/liqpay => payLiqPay(Order $order)
    // - /{locale}/checkout/{order}/pay/liqpay => payLiqPay(string $locale, Order $order)
    $locale = null;
    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } else {
        $locale = is_string($localeOrOrder) ? $localeOrOrder : null;
    }

    if (! $order instanceof Order) {
        abort(404);
    }

    // защита от "чужих" заказов
    // Если заказ привязан к клиенту - проверяем, что текущий пользователь это его владелец
    if ($order->clients_id) {
        // Заказ привязан к клиенту - проверяем авторизацию и владельца
        // Используем строгое сравнение с приведением типов
        $orderClientId = (int) $order->clients_id;
        $currentUserId = auth()->check() ? (int) auth()->id() : null;

        if (!$currentUserId || $currentUserId !== $orderClientId) {
            abort(403);
        }
    }
    // Для гостевых заказов (clients_id = null) разрешаем доступ всем

    if ($order->payment !== PaymentMethodEnum::LIQPAY) {
        abort(404);
    }

    $order->loadMissing('clients');

    $expectedGrandTotal = $this->calculatePayableTotal($order);
    if (round((float) $order->grand_total, 2) !== $expectedGrandTotal) {
        $order->grand_total = $expectedGrandTotal;
        $order->save();
        $order->refresh();
    }

    $sessionEmail = trim((string) session('checkout.form_data.contact_email', ''));
    $clientEmail = trim((string) ($order->clients?->email ?: $sessionEmail));
    $emailRequired = $clientEmail === '';

    $effectiveLocale = $locale ?: app()->getLocale();
    $liqpayLocale = in_array($effectiveLocale, ['uk', 'ru', 'en'], true) ? $effectiveLocale : 'uk';
    $liqpayForm = LiqPayService::make()->formForOrder($order, $liqpayLocale);

    return view(front_view('checkout.liqpay'), [
        'order'     => $order,
        'clientEmail' => $clientEmail,
        'emailRequired' => $emailRequired,
        'liqpayForm'=> $liqpayForm,
    ]);
}

public function payPayparts(Request $request, $localeOrOrder, ?Order $order = null)
{
    $locale = null;
    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } else {
        $locale = is_string($localeOrOrder) ? $localeOrOrder : null;
    }

    if (! $order instanceof Order) {
        abort(404);
    }

    if ($order->clients_id) {
        $orderClientId = (int) $order->clients_id;
        $currentUserId = auth()->check() ? (int) auth()->id() : null;

        if (! $currentUserId || $currentUserId !== $orderClientId) {
            abort(403);
        }
    }

    if ($order->payment !== PaymentMethodEnum::PAYPARTS) {
        abort(404);
    }

    $order->loadMissing(['clients', 'paypartsBank', 'lastPaypartsTransaction']);

    $expectedGrandTotal = $this->calculatePayableTotal($order);
    if (round((float) $order->grand_total, 2) !== $expectedGrandTotal) {
        $order->grand_total = $expectedGrandTotal;
        $order->save();
        $order->refresh();
        $order->loadMissing(['clients', 'paypartsBank', 'lastPaypartsTransaction']);
    }

    $bank = $order->paypartsBank;
    $transaction = $order->lastPaypartsTransaction;
    $sessionKey = 'checkout.payparts.' . $order->id;
    $paypartsSession = (array) session($sessionKey, []);
    $clientEmail = trim((string) ($order->clients?->email ?: ($paypartsSession['contact_email'] ?? '')));
    $editEmail = $request->boolean('edit_email');
    $emailRequired = $editEmail || $clientEmail === '' || ! filter_var($clientEmail, FILTER_VALIDATE_EMAIL);
    $paymentUrl = $transaction?->token
        ? PrivatBankPaypartsService::make()->paymentUrl((string) $transaction->token)
        : null;
    $error = null;

    if ($paymentUrl && $transaction) {
        $logKey = 'payparts_checkout_payload_logged:' . $transaction->id;
        if (\Illuminate\Support\Facades\Cache::add($logKey, true, now()->addMinutes(30))) {
            \Illuminate\Support\Facades\Log::info('Payparts checkout page payload', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'bank_id' => $bank?->id,
                'order_token' => $transaction->token,
                'request_payload' => $transaction->request_payload,
                'response_payload' => $transaction->response_payload,
                'payment_url' => $paymentUrl,
            ]);
        }
    }

    if (! $bank || ! $bank->is_active) {
        $error = st('cart.payment.payparts_bank_unavailable', 'Обраний банк зараз недоступний.');
    }

    if (! $emailRequired && ! $paymentUrl && ! $error) {
        $planKey = (string) ($paypartsSession['plan_key'] ?? '');
        $plans = collect($bank->plansForAmount((float) $order->grand_total));
        $plan = $plans->firstWhere('key', $planKey);

        if (! $plan) {
            $order->payparts_status = 'payment_failed';
            $order->save();
            $error = st('cart.payment.payparts_plan_unavailable', 'Обрані умови оплати частинами недоступні для цієї суми.');
        } else {
            try {
                $transaction = PrivatBankPaypartsService::make()->createPayment(
                    order: $order,
                    bank: $bank,
                    merchantType: (string) ($plan['merchant_type'] ?? ''),
                    partsCount: (int) ($plan['parts_count'] ?? 0),
                    customerPhone: (string) ($paypartsSession['contact_phone'] ?? $order->clients?->phone ?? ''),
                    customerEmail: (string) ($paypartsSession['contact_email'] ?? $order->clients?->email ?? ''),
                    locale: $locale ?: app()->getLocale(),
                );

                $order->payparts_status = 'payment_redirected';
                $order->save();

                $paymentUrl = PrivatBankPaypartsService::make()->paymentUrl((string) $transaction->token);
            } catch (\Throwable $e) {
                \Log::error('PrivatBank payparts create payment failed', [
                    'order_id' => $order->id,
                    'bank_id' => $bank->id,
                    'plan_key' => $planKey,
                    'error' => $e->getMessage(),
                ]);

                $order->payparts_status = 'payment_failed';
                $order->save();
                $error = st('cart.payment.payparts_create_failed', 'Не вдалося перейти до оплати частинами. Спробуйте ще раз або оберіть інший спосіб оплати.');
            }
        }
    }

    return view(front_view('checkout.payparts'), [
        'order' => $order,
        'bank' => $bank,
        'transaction' => $transaction,
        'paymentUrl' => $paymentUrl,
        'error' => $error,
        'clientEmail' => $clientEmail,
        'emailRequired' => $emailRequired,
        'editEmail' => $editEmail,
    ]);
}
public function savePaypartsEmail(Request $request, $localeOrOrder, ?Order $order = null)
{
    $locale = null;
    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } else {
        $locale = is_string($localeOrOrder) ? $localeOrOrder : null;
    }
    if (! $order instanceof Order || $order->payment !== PaymentMethodEnum::PAYPARTS) {
        abort(404);
    }
    if ($order->clients_id) {
        $currentUserId = auth()->check() ? (int) auth()->id() : null;
        if (! $currentUserId || $currentUserId !== (int) $order->clients_id) {
            abort(403);
        }
    }
    $validated = $request->validate(['contact_email' => 'required|email|max:255']);
    $email = trim((string) $validated['contact_email']);
    $order->loadMissing('clients');
    if ($order->clients) {
        $order->clients->forceFill(['email' => $email])->save();
    }
    $sessionKey = 'checkout.payparts.' . $order->id;
    $paypartsSession = (array) session($sessionKey, []);
    $paypartsSession['contact_email'] = $email;
    session([$sessionKey => $paypartsSession]);
    $routeName = in_array($locale, ['ru', 'en'], true) ? 'localized.checkout.pay.payparts' : 'checkout.pay.payparts';
    $routeParams = in_array($locale, ['ru', 'en'], true) ? ['locale' => $locale, 'order' => $order] : ['order' => $order];
    return redirect()->route($routeName, $routeParams)->with('success', st('checkout.liqpay.email_saved', 'Email збережено.'));
}

public function payPaypartsStatus($localeOrOrder, ?Order $order = null)
{
    $locale = null;
    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } else {
        $locale = is_string($localeOrOrder) ? $localeOrOrder : null;
    }

    if (! $order instanceof Order) {
        abort(404);
    }

    if ($order->clients_id) {
        $orderClientId = (int) $order->clients_id;
        $currentUserId = auth()->check() ? (int) auth()->id() : null;

        if (! $currentUserId || $currentUserId !== $orderClientId) {
            abort(403);
        }
    }

    if ($order->payment !== PaymentMethodEnum::PAYPARTS) {
        abort(404);
    }

    $order->loadMissing(['paypartsBank', 'lastPaypartsTransaction']);
    $transaction = $order->lastPaypartsTransaction;
    $bank = $order->paypartsBank;

    if ($transaction && $bank && in_array((string) $order->payparts_status, ['payment_redirected', 'pending_payment'], true)) {
        try {
            app(\App\Services\PaypartsStatusSyncService::class)->sync($transaction);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('Payparts status sync skipped', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    $order->refresh();

    $locale ??= app()->getLocale();
    $successRoute = in_array($locale, ['ru', 'en'], true)
        ? route('localized.checkout.success', ['locale' => $locale, 'order' => $order])
        : route('checkout.success', ['order' => $order]);

    return response()->json([
        'order_status' => $order->status?->value ?? (string) $order->status,
        'payparts_status' => $order->payparts_status,
        'success' => $order->payparts_status === 'payment_success',
        'failed' => $order->payparts_status === 'payment_failed',
        'success_url' => $successRoute,
    ]);
}

public function saveLiqPayEmail(Request $request, $localeOrOrder, ?Order $order = null)
{
    // Support both routes:
    // - /checkout/{order}/pay/liqpay/email => saveLiqPayEmail(Request $request, Order $order)
    // - /{locale}/checkout/{order}/pay/liqpay/email => saveLiqPayEmail(Request $request, string $locale, Order $order)
    $locale = null;
    if ($localeOrOrder instanceof Order) {
        $order = $localeOrOrder;
    } else {
        $locale = is_string($localeOrOrder) ? $localeOrOrder : null;
    }

    if (! $order instanceof Order) {
        abort(404);
    }

    if ($order->clients_id) {
        $orderClientId = (int) $order->clients_id;
        $currentUserId = auth()->check() ? (int) auth()->id() : null;

        if (! $currentUserId || $currentUserId !== $orderClientId) {
            abort(403);
        }
    }

    if ($order->payment !== PaymentMethodEnum::LIQPAY) {
        abort(404);
    }

    $validated = $request->validate([
        'contact_email' => 'required|email|max:255',
    ]);

    $email = trim((string) $validated['contact_email']);

    $order->loadMissing('clients');

    if ($order->clients) {
        $order->clients->email = $email;
        $order->clients->save();
    }

    $sessionData = session('checkout.form_data', []);
    $sessionData['contact_email'] = $email;
    session(['checkout.form_data' => $sessionData]);

    return redirect()
        ->route(
            in_array(app()->getLocale(), ['ru', 'en'], true) ? 'localized.checkout.pay.liqpay' : 'checkout.pay.liqpay',
            in_array(app()->getLocale(), ['ru', 'en'], true)
                ? ['locale' => app()->getLocale(), 'order' => $order]
                : ['order' => $order]
        )
        ->with('success', st('checkout.liqpay.email_saved', 'Email збережено. Тепер можна перейти до оплати.'));
}

private function isPaypartsEnabledForClient(?Client $client): bool
{
    if (! (bool) Setting::payparts('enabled', false)) {
        return false;
    }

    $audience = (string) Setting::payparts('button_audience', 'all');
    if ($audience !== 'specific') {
        return true;
    }

    if (! $client) {
        return false;
    }

    $allowedValues = array_values(array_filter((array) Setting::payparts('button_client_ids', []), fn ($value): bool => (string) $value !== ''));

    if ($allowedValues === []) {
        return false;
    }

    $allowedIds = array_values(array_filter(array_map('intval', $allowedValues)));

    if (in_array((int) $client->id, $allowedIds, true)) {
        return true;
    }

    $clientPhone = $this->normalizePaypartsAudiencePhone((string) ($client->phone ?? ''));

    if ($clientPhone === '') {
        return false;
    }

    $allowedPhones = collect($allowedValues)
        ->map(fn ($value): string => $this->normalizePaypartsAudiencePhone((string) $value))
        ->filter()
        ->merge(
            Client::query()
                ->whereIn('id', $allowedIds)
                ->pluck('phone')
                ->map(fn ($phone): string => $this->normalizePaypartsAudiencePhone((string) $phone))
                ->filter()
        )
        ->unique()
        ->values();

    return $allowedPhones->contains($clientPhone);
}

private function normalizePaypartsAudiencePhone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?: '';

    if (str_starts_with($digits, '380')) {
        return $digits;
    }

    if (str_starts_with($digits, '80')) {
        return '3' . $digits;
    }

    if (str_starts_with($digits, '0')) {
        return '38' . $digits;
    }

    if (strlen($digits) === 9) {
        return '380' . $digits;
    }

    return $digits;
}

private function availablePaypartsBanksForCheckout(?Client $client, float $amount)
{
    return PaypartsBank::query()
        ->active()
        ->visibleForClient($client)
        ->orderBy('id')
        ->get()
        ->map(function (PaypartsBank $bank) use ($amount): array {
            $rules = $bank->plansForAmount($amount);
            $minAmount = collect($bank->rules ?? [])
                ->filter(fn ($rule): bool => (bool) ($rule['is_active'] ?? true))
                ->map(fn ($rule): float => (float) ($rule['min_amount'] ?? 0))
                ->filter(fn (float $value): bool => $value > 0)
                ->min();

            return [
                'id' => $bank->id,
                'bank_type' => $bank->bank_type,
                'bank_label' => $bank->bankType()?->label() ?? $bank->bank_type,
                'name' => $bank->localizedText('name', app()->getLocale(), $bank->bankType()?->label()) ?? $bank->bankType()?->label() ?? $bank->bank_type,
                'description' => $bank->localizedText('description', app()->getLocale()),
                'terms' => $bank->localizedText('terms', app()->getLocale()),
                'rules' => $rules,
                'min_amount' => $minAmount,
            ];
        })
        ->values();
}

private function calculatePayableTotal(Order $order): float
{
    $itemsTotal = (float) ($order->total_price ?? 0);
    $adjustmentsTotal = (float) $order->adjustments()->sum('amount');
    $bonusTotal = max(0, (float) ($order->sale_sum ?? 0));
    $shipping = (float) ($order->shipping_price ?? 0);

    $goodsTotal = max(0, round($itemsTotal + $adjustmentsTotal - $bonusTotal, 2));

    return max(0, round($goodsTotal + $shipping, 2));
}

private function calculateCheckoutPaypartsTotal(
    float $itemsTotal,
    float $discount,
    float $bonusUsed,
    string $shippingMethod,
    float $shippingPrice
): float {
    $goodsTotal = max(0, round($itemsTotal - max(0, $discount) - max(0, $bonusUsed), 2));
    $shipping = $shippingMethod === 'pickup' ? 0.0 : max(0, $shippingPrice);

    return max(0, round($goodsTotal + $shipping, 2));
}

private function recalculateOrderShipping(Order $order): void
{
    $shippingMethod = (string) ($order->shipping_method ?? ($order->self_pickup ? 'pickup' : 'delivery'));

    if ($shippingMethod !== 'delivery' || (bool) ($order->self_pickup ?? false)) {
        $order->shipping_price = 0;
        $order->shipping_total = 0;

        return;
    }

    // В этом месте адрес/ID адреса могли только что измениться,
    // поэтому принудительно перезагружаем связь, а не loadMissing.
    $order->load('clientAddress');

    $delivery = $this->deliveryCalculation->calculateDelivery($order, $order->resolveDeliveryBaseAmount());
    $shipping = max(0, (float) ($delivery['price'] ?? 0));

    $order->shipping_price = $shipping;
    $order->shipping_total = $shipping;
}

private function syncCheckoutStateWithCart(array $items, array $sessionData): array
{
    $currentSignature = $this->buildCartSignature($items);
    $previousSignature = session('checkout.cart_signature');

    $cartChanged = $previousSignature !== null && $previousSignature !== $currentSignature;
    $cartEmpty = count($items) === 0;

    if ($cartChanged || $cartEmpty) {
        session([
            'checkout.selected_promo' => 'none',
            'checkout.promo_discount' => 0,
        ]);

        unset(
            $sessionData['use_bonus'],
            $sessionData['bonus_amount'],
            $sessionData['selected_promo'],
            $sessionData['payment_method'],
            $sessionData['payparts_bank_id'],
            $sessionData['payparts_plan_key'],
            $sessionData['payparts_financial_phone']
        );
        session(['checkout.form_data' => $sessionData]);
    }

    session(['checkout.cart_signature' => $currentSignature]);

    return $sessionData;
}

private function buildCartSignature(array $items): string
{
    if (empty($items)) {
        return 'empty';
    }

    $normalized = collect($items)
        ->map(function ($item): array {
            if (is_array($item)) {
                return [
                    'product_id' => (int) ($item['product_id'] ?? ($item['product']['id'] ?? 0)),
                    'qty' => (float) ($item['qty'] ?? $item['quantity'] ?? 0),
                    'unit_price' => (float) ($item['unit_price'] ?? $item['price'] ?? 0),
                    'meta' => $item['meta'] ?? null,
                ];
            }

            return [
                'product_id' => (int) ($item->product_id ?? ($item->product->id ?? 0)),
                'qty' => (float) ($item->qty ?? $item->quantity ?? 0),
                'unit_price' => (float) ($item->unit_price ?? $item->price ?? 0),
                'meta' => $item->meta ?? null,
            ];
        })
        ->sortBy(fn (array $row) => json_encode($row))
        ->values()
        ->all();

    return sha1(json_encode($normalized));
}

private function isCartEmpty(array $items, array $info): bool
{
    if (!empty($items)) {
        return false;
    }

    $qty = (int) ($info['qty'] ?? 0);

    return $qty <= 0;
}

private function resetCheckoutDynamicState(): void
{
    $sessionData = session('checkout.form_data', []);
    unset($sessionData['use_bonus'], $sessionData['bonus_amount']);

    session([
        'checkout.form_data' => $sessionData,
        'checkout.selected_promo' => 'none',
        'checkout.promo_discount' => 0,
    ]);
}

/**
 * Проверка условий для акции (время, день недели, способ получения, диаметры)
 */
private function checkPromoConditions(
    TimeDiscount $discount,
    string $shippingMethod,
    string $deliveryMode,
    ?string $deliveryDate,
    ?string $deliveryTime,
    $products
): bool {
    // 1. Проверка способа получения (channels)
    $channels = $discount->channels ?? [];
    if (!empty($channels)) {
        $expectedChannel = $shippingMethod === 'pickup' ? 'pickup' : 'delivery';
        if (!in_array($expectedChannel, $channels)) {
            \Log::info('Promo condition failed: channel mismatch', [
                'discount_id' => $discount->id,
                'expected_channel' => $expectedChannel,
                'available_channels' => $channels,
            ]);
            return false;
        }
    }

    // 2. Проверка характеристик продуктов (диаметры)
    $characteristicValueIds = $discount->characteristicValues()->pluck('bs_shop_time_discount_characteristic_values.characteristic_value_id')->toArray();
    if (!empty($characteristicValueIds)) {
        $hasMatchingProduct = false;
        foreach ($products as $product) {
            // Получаем characteristic_value_id через pivot таблицу bs_product_characteristic_value
            $productCharValueIds = $product->characteristicValues()
                ->pluck('bs_product_characteristic_value.characteristic_value_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            
            if (array_intersect($characteristicValueIds, $productCharValueIds)) {
                $hasMatchingProduct = true;
                break;
            }
        }
        if (!$hasMatchingProduct) {
            return false;
        }
    }

    // 3. Проверка дня недели и времени
    $now = now();
    $referenceDate = $now;
    $referenceTime = $now->format('H:i:s');
    $referenceWeekday = (int) $now->isoWeekday(); // 1-7 (Mon-Sun)

    // Если выбрана конкретная дата доставки
    if ($deliveryDate) {
        try {
            $referenceDate = Carbon::parse($deliveryDate);
            $referenceWeekday = (int) $referenceDate->isoWeekday();
        } catch (\Exception $e) {
            // Если не удалось распарсить дату, используем текущую
        }
    }

    // Если выбрано конкретное время доставки
    if ($deliveryTime) {
        // Формат может быть "12:00" или "12:00-12:15"
        $timeParts = explode('-', $deliveryTime);
        $timeStr = trim($timeParts[0]);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeStr, $matches)) {
            $referenceTime = sprintf('%02d:%02d:00', (int)$matches[1], (int)$matches[2]);
        }
    } elseif ($deliveryMode === 'asap') {
        // Если "как можно скорее", используем текущее время
        $referenceTime = $now->format('H:i:s');
    }

    // Проверка дня недели
    $days = $discount->days ?? [];
    if (!empty($days) && !in_array($referenceWeekday, $days, true)) {
        return false;
    }

    // Проверка временного окна
    if (!$discount->matchesTimeWindow($referenceTime)) {
        return false;
    }

    return true;
}

/**
 * Проверка условий акций для AJAX запроса
 */
public function checkPromoConditionsAjax(Request $request)
{
    $shippingMethod = $request->input('shipping_method', 'delivery');
    $deliveryMode = $request->input('delivery_mode', 'asap');
    $deliveryDate = $request->input('delivery_date');
    $deliveryTime = $request->input('delivery_time');

    $items = $this->cart->items();
    $productIds = collect($items)->pluck('product_id');

    // Получаем все активные акции
    $timeDiscounts = TimeDiscount::query()
        ->where('is_active', true)
        ->get()
        ->filter(function (TimeDiscount $d) use ($productIds) {
            return $d->hasEligibleProducts($productIds);
        });

    // Получаем продукты с характеристиками
    $products = Product::with('characteristicValues')
        ->whereIn('id', $productIds)
        ->get();

    $locale = app()->getLocale();
    $promos = [];

    foreach ($timeDiscounts as $discount) {
        $name = $discount->getTranslation('name', $locale);
        $p = number_format((float)$discount->percent, 2, '.', '');
        $value = 'time:' . $discount->id;

        // Проверяем условия
        $isActive = $this->checkPromoConditions(
            $discount,
            $shippingMethod,
            $deliveryMode,
            $deliveryDate,
            $deliveryTime,
            $products
        );
        $previewDiscount = $this->calculateCheckoutTimeDiscountPreview($discount, $products, $items);
        $isActive = $isActive && $previewDiscount > 0;

        \Log::info('Promo condition check result', [
            'discount_id' => $discount->id,
            'name' => $name,
            'shipping_method' => $shippingMethod,
            'is_active' => $isActive,
            'channels' => $discount->channels ?? [],
        ]);

        $promos[] = [
            'value' => $value,
            'is_active' => $isActive,
        ];
    }

    return response()->json([
        'promos' => $promos,
    ]);
}

private function calculateCheckoutTimeDiscountPreview(TimeDiscount $discount, $products, array $items): float
{
    $rows = collect($items)->map(function ($item): array {
        $row = is_object($item) ? (array) $item : (array) $item;

        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'qty' => (int) ($row['qty'] ?? 0),
            'unit_price' => (float) ($row['price'] ?? $row['unit_price'] ?? 0),
            'modifiers' => $row['modifiers'] ?? [],
        ];
    })->values();

    $percent = (float) ($discount->percent ?? 0);
    $eachN = (int) ($discount->nth_item ?? 0);
    if ($percent <= 0 || $eachN < 1 || $rows->isEmpty()) {
        return 0.0;
    }

    $units = [];

    foreach ($rows as $idx => $row) {
        $pid = (int) ($row['product_id'] ?? 0);
        $qty = (int) ($row['qty'] ?? 0);
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }

        $product = $products->firstWhere('id', $pid);
        if (! $product || $product->excludedFromPromotions()) {
            continue;
        }

        if (! $this->matchesCheckoutTimeDiscountScopeInline($discount, $product, $row)) {
            continue;
        }

        $mods = collect($row['modifiers'] ?? [])->map(fn ($m) => is_object($m) ? (array) $m : (array) $m);
        $modsSum = (float) $mods->sum(fn (array $m) => (float) ($m['price_modifier'] ?? 0));
        $unitPrice = (float) ($row['unit_price'] ?? 0) + $modsSum;

        for ($i = 0; $i < $qty; $i++) {
            $units[] = [
                'row_index' => $idx,
                'price' => $unitPrice,
            ];
        }
    }

    if (count($units) < $eachN) {
        return 0.0;
    }

    $grouping = (string) ($discount->grouping_mode ?: TimeDiscount::GROUP_PRICE_SORTED);
    $target = (string) ($discount->apply_target ?: TimeDiscount::TARGET_CHEAPEST);
    $index = (int) ($discount->apply_index ?? 0);

    if ($grouping === TimeDiscount::GROUP_PRICE_SORTED) {
        usort($units, fn (array $a, array $b) => $b['price'] <=> $a['price']);
    }

    $chunks = array_chunk($units, $eachN);
    $chunks = array_values(array_filter($chunks, fn (array $chunk) => count($chunk) === $eachN));

    $amount = 0.0;
    foreach ($chunks as $chunk) {
        $recipient = null;
        if ($target === TimeDiscount::TARGET_MOST_EXPENSIVE) {
            $recipient = collect($chunk)->sortByDesc('price')->first();
        } elseif ($target === TimeDiscount::TARGET_INDEX) {
            $sorted = collect($chunk)->sortBy('price')->values();
            $pos = max(1, min($eachN, $index > 0 ? $index : 1));
            $recipient = $sorted->get($pos - 1);
        } else {
            $recipient = collect($chunk)->sortBy('price')->first();
        }

        if (! is_array($recipient)) {
            continue;
        }

        $amount += ((float) ($recipient['price'] ?? 0)) * ($percent / 100);
    }

    return round(max(0.0, $amount), 2);
}

private function matchesCheckoutTimeDiscountScopeInline(TimeDiscount $discount, Product $product, array $row): bool
{
    try {
        static $matchProductMethod;
        static $matchCategoryMethod;
        static $matchCharacteristicsMethod;

        if (! $matchProductMethod) {
            $matchProductMethod = new \ReflectionMethod(TimeDiscount::class, 'matchesProduct');
            $matchProductMethod->setAccessible(true);
        }
        if (! $matchCategoryMethod) {
            $matchCategoryMethod = new \ReflectionMethod(TimeDiscount::class, 'matchesCategory');
            $matchCategoryMethod->setAccessible(true);
        }
        if (! $matchCharacteristicsMethod) {
            $matchCharacteristicsMethod = new \ReflectionMethod(TimeDiscount::class, 'matchesCharacteristics');
            $matchCharacteristicsMethod->setAccessible(true);
        }

        if (! (bool) $matchProductMethod->invoke($discount, $product)) {
            return false;
        }
        if (! (bool) $matchCategoryMethod->invoke($discount, $product)) {
            return false;
        }

        $item = new OrderItem([
            'product_id' => $product->id,
            'qty' => (int) ($row['qty'] ?? 0),
            'unit_price' => (float) ($row['unit_price'] ?? 0),
        ]);
        $item->setRelation('product', $product);

        if (! (bool) $matchCharacteristicsMethod->invoke($discount, $item)) {
            return false;
        }
    } catch (\Throwable) {
        return false;
    }

    return true;
}

private function canAccessCustomerOrder(Order $order): bool
{
    if (! $order->clients_id) {
        return true;
    }

    $orderClientId = (int) $order->clients_id;
    $currentUserId = auth()->check() ? (int) auth()->id() : null;

    if ($currentUserId && $currentUserId === $orderClientId) {
        return true;
    }

    return $this->hasRecentCheckoutOrderAccess($order);
}

private function rememberRecentCheckoutOrder(Order $order): void
{
    $key = 'checkout_recent_success_orders';
    $ttlSeconds = 30 * 60;
    $recent = session($key, []);

    if (! is_array($recent)) {
        $recent = [];
    }

    $now = now()->timestamp;

    $recent = array_filter($recent, fn ($timestamp) => is_numeric($timestamp) && ((int) $timestamp + $ttlSeconds) >= $now);
    $recent[(string) $order->id] = $now;

    session([$key => $recent]);
}

private function hasRecentCheckoutOrderAccess(Order $order): bool
{
    $key = 'checkout_recent_success_orders';
    $ttlSeconds = 30 * 60;
    $recent = session($key, []);

    if (! is_array($recent)) {
        return false;
    }

    $timestamp = $recent[(string) $order->id] ?? null;
    if (! is_numeric($timestamp)) {
        return false;
    }

    return ((int) $timestamp + $ttlSeconds) >= now()->timestamp;
}

}
