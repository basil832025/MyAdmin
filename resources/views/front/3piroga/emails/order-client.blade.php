<x-mail::message>
<x-slot name="header">
<x-mail::header :url="config('app.url')">
<img src="{{ asset('images/logo.svg') }}" alt="{{ st('header.logo_alt', 'Три пироги') }}" style="max-height: 56px; width: auto;">
</x-mail::header>
</x-slot>

# {{ st('order.email.thank_you', 'Дякуємо за ваше замовлення!') }}

{{ st('order.email.greeting', 'Шановний клієнте!') }}

{{ st('order.email.thank_you_message', 'Дякуємо Вам за замовлення в нашому ресторані «Три Пироги».') }}

## {{ st('order.email.order_info', 'Інформація про замовлення') }}

**{{ st('order.email.order_number', 'Номер замовлення') }}:** №{{ $order->number ?? $order->id }}  
**{{ st('order.email.order_date', 'Дата створення') }}:** {{ ($order->placedAt() ?? $order->created_at)->format('d.m.Y') }}  
**{{ st('order.email.order_status', 'Статус') }}:** {{ $order->status->getLabel() }}

@php
    $clientPhone = \App\Support\Phone::formatUa($order->clients?->phone ?? $order->phone ?? null);
@endphp

@if($clientPhone)
**{{ st('order.email.client_phone', 'Телефон') }}:** {{ $clientPhone }}
@endif

@if($order->date_order)
**{{ st('order.email.delivery_date', 'Дата доставки') }}:** {{ \Carbon\Carbon::parse($order->date_order)->format('d.m.Y') }}
@endif

@if($order->time_order)
@php
    $deliveryTimeRaw = $order->time_order;
    if ($deliveryTimeRaw instanceof \DateTimeInterface) {
        $deliveryTime = $deliveryTimeRaw->format('H:i');
    } else {
        $deliveryTime = trim((string) $deliveryTimeRaw);

        // "13:00:00" -> "13:00"
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $deliveryTime)) {
            $deliveryTime = substr($deliveryTime, 0, 5);
        } else {
            // "2026-05-03 13:00:00" -> "13:00"
            try {
                $deliveryTime = \Carbon\Carbon::parse($deliveryTime)->format('H:i');
            } catch (\Throwable) {
                // keep as-is
            }
        }
    }
@endphp
**{{ st('order.email.delivery_time', 'Час доставки') }}:** {{ $deliveryTime }}
@endif

## {{ st('order.email.delivery_address', 'Адреса доставки') }}

@php
    $deliveryAddress = '';
    if ($order->clientAddress) {
        $addrParts = [];
        if ($order->clientAddress->city) $addrParts[] = $order->clientAddress->city;
        if ($order->clientAddress->street) $addrParts[] = st('address.parts.street_prefix', 'вулиця') . ' ' . $order->clientAddress->street;
        if ($order->clientAddress->house) $addrParts[] = st('address.parts.house_short', 'д.') . $order->clientAddress->house;
        if ($order->clientAddress->apartment) $addrParts[] = st('address.parts.apartment_short', 'кв.') . $order->clientAddress->apartment;
        $deliveryAddress = implode(', ', $addrParts);
    }
@endphp

{{ $deliveryAddress ?: st('order.email.no_address', '—') }}

## {{ st('order.email.delivery_method', 'Спосіб доставки') }}

{{ $order->self_pickup ? st('order.email.pickup', 'Самовивіз') : st('order.email.delivery', 'Доставка кур\'єром') }}

## {{ st('order.email.payment_method', 'Спосіб оплати') }}

@php
    $mailLocale = app()->getLocale();
    $paymentMethod = $order->payment instanceof \App\Enums\PaymentMethodEnum
        ? $order->payment
        : \App\Enums\PaymentMethodEnum::tryFrom((int) ($order->payment ?? 0));

    $paymentLabel = match ($paymentMethod) {
        \App\Enums\PaymentMethodEnum::LIQPAY => st('cart.payment.liqpay', 'Онлайн-оплата карткою'),
        \App\Enums\PaymentMethodEnum::POS => st('cart.payment.card_on_delivery', 'Оплата через POS-термінал при отриманні'),
        \App\Enums\PaymentMethodEnum::CASH => st('cart.payment.cash', 'Готівкою при отриманні'),
        \App\Enums\PaymentMethodEnum::ORG_TRANSFER,
        \App\Enums\PaymentMethodEnum::INVOICE => st('cart.payment.invoice', 'Безготівковий розрахунок за рахунком для юридичних осіб'),
        \App\Enums\PaymentMethodEnum::PAYPARTS => $paymentMethod?->label($mailLocale) ?? \App\Enums\PaymentMethodEnum::PAYPARTS->label($mailLocale),
        default => $paymentMethod?->label($mailLocale) ?? \App\Enums\PaymentMethodEnum::CARD->label($mailLocale),
    };
@endphp

{{ $paymentLabel }}

## {{ st('order.email.items', 'Товари') }}

@php
    $order->load([
        'items.product.parent.productCharacteristicValues.characteristic.svgImage',
        'items.product.productCharacteristicValues.characteristic.svgImage',
        'items.product.productCharacteristicValues.characteristicValue',
        'adjustments'
    ]);
    $items = $order->items;
    
    // Сумма товаров без скидок
    $itemsTotal = (float)($order->total_price ?? 0);
    
    // Все скидки (adjustments с отрицательными amount)
    $adjustments = $order->adjustments()->whereNull('shop_order_item_id')->get();
    $discountTotal = 0;
    $discountsList = [];
    
    foreach ($adjustments as $adj) {
        $amount = (float)($adj->amount ?? 0);
        if ($amount < 0) {
            $discountAmount = abs($amount);
            $discountTotal += $discountAmount;
            $discountsList[] = [
                'label' => $adj->label ?? st('order.email.discount', 'Знижка'),
                'amount' => $discountAmount,
            ];
        }
    }

    $shipping = (float)($order->shipping_price ?? 0);
    $bonusesSpent = method_exists($order, 'resolveSpentBonuses')
        ? $order->resolveSpentBonuses()
        : max(0, (float)($order->sale_sum ?? 0));
    
    // Итоговая сумма с учетом скидок, бонусов и доставки
    $total = $order->grand_total ?? ($itemsTotal - $discountTotal - $bonusesSpent + $shipping);
@endphp

<x-mail::table>
| {{ st('order.email.item_name', 'Назва') }} | {{ st('order.email.quantity', 'Кількість') }} | {{ st('order.email.unit_price', 'Ціна за одиницю') }} | {{ st('order.email.total', 'Сума') }} |
|:---------|:----------:|:---------------:|:-----:|
@foreach($items as $item)
@php
    $product = $item->product;
    $snapshot = $item->product_snapshot ?? [];
    $name = $snapshot['name'] ?? $snapshot['title'] ?? null;
    
    if (!$name && $product) {
        $parent = $product->parent ?? $product;
        $name = $parent->display_name ?? $parent->displayName ?? $parent->title ?? st('order.email.product', 'Товар');
    }
    $name = $name ?? st('order.email.product', 'Товар');
    
    // Получаем характеристики (размер, вес, но не персоны)
    $productChars = [];
    $personSlug = 'persons';
    
    if ($product && $product->relationLoaded('productCharacteristicValues')) {
        $charValues = $product->productCharacteristicValues
            ->filter(function($cv) use ($personSlug) {
                $char = $cv->characteristic;
                if (!$char) return false;
                return $char->is_main_tab && $char->is_active && ($char->slug ?? null) !== $personSlug;
            });
        
        foreach ($charValues as $cv) {
            $char = $cv->characteristic;
            if (!$char) continue;
            $value = $cv->value_text ?? ($cv->characteristicValue->value ?? null);
            if ($value) {
                $productChars[] = $value;
            }
        }
    } elseif ($product) {
        $charValues = $product->productCharacteristicValues()
            ->whereHas('characteristic', function($q) use ($personSlug) {
                $q->where('is_main_tab', 1)
                  ->where('is_active', 1)
                  ->where('slug', '!=', $personSlug);
            })
            ->with(['characteristic.svgImage', 'characteristicValue'])
            ->get();
        
        foreach ($charValues as $cv) {
            $char = $cv->characteristic;
            if (!$char) continue;
            $value = $cv->value_text ?? ($cv->characteristicValue->value ?? null);
            if ($value) {
                $productChars[] = $value;
            }
        }
    }
    
    // Если не нашли в модели, пробуем из snapshot (исключаем персоны)
    if (empty($productChars)) {
        $characteristics = $snapshot['characteristics'] ?? [];
        if (!empty($characteristics) && is_array($characteristics)) {
            foreach ($characteristics as $char) {
                if (is_array($char)) {
                    $charSlug = $char['slug'] ?? null;
                    if ($charSlug === 'persons' || $charSlug === 'osoby') {
                        continue;
                    }
                    $charValue = $char['value'] ?? $char['text'] ?? null;
                    if ($charValue) {
                        $productChars[] = $charValue;
                    }
                } elseif (is_string($char)) {
                    $productChars[] = $char;
                }
            }
        }
    }
    
    // Добавляем характеристики к названию
    if (!empty($productChars)) {
        $name .= ' (' . implode(', ', $productChars) . ')';
    }
    
    $qty = (int)($item->qty ?? 1);
    $price = (float)($item->unit_price ?? 0);
    if (!empty($item->subtotal) && (float)$item->subtotal > 0) {
        $subtotal = (float)$item->subtotal;
    } elseif (!empty($item->total) && (float)$item->total > 0) {
        $subtotal = (float)$item->total;
    } else {
        $subtotal = $qty * $price;
    }
@endphp
| {{ $name }} | {{ $qty }} {{ st('order.email.pcs', 'шт.') }} | {{ number_format($price, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }} | {{ number_format($subtotal, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }} |
@endforeach
</x-mail::table>

## {{ st('order.email.total', 'Разом') }}

**{{ st('order.email.items_total', 'Товари') }}:** {{ number_format($itemsTotal, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }}

**{{ st('order.email.discount_total', 'Знижка') }}:** -{{ number_format($discountTotal, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }}

@if(!empty($discountsList))
@foreach($discountsList as $discount)
**{{ $discount['label'] }}:** -{{ number_format($discount['amount'], 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }}
@endforeach
@endif

**{{ st('order.email.shipping', 'Доставка') }}:** {{ number_format($shipping, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }}

**{{ st('order.email.bonuses_spent', 'Списано бонусів') }}:** {{ number_format($bonusesSpent, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }}

**{{ st('order.email.total_to_pay', 'Разом до оплати') }}:** {{ number_format($total, 2, '.', ' ') }} {{ st('order.email.currency', 'грн') }}

@if($order->notes)
## {{ st('order.email.comments', 'Коментарі') }}

{{ $order->notes }}
@endif

---

{{ st('order.email.closing', 'Дякуємо за ваше замовлення!') }}

{{ st('order.email.signature', 'З повагою, команда «Три Пироги»') }}
</x-mail::message>
