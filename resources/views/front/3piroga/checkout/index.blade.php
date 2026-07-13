@section('page', 'checkout')
@extends(front_view('layouts.app'))
@section('title','Мій заказ')

@php
    $locale = app()->getLocale();
    $isLocalized = in_array($locale, ['ru', 'en'], true);
    $routePrefix = $isLocalized ? 'localized.' : '';
    $routeParams = $isLocalized ? ['locale' => $locale] : [];

    $addUrl    = route($routePrefix . 'cart.add', $routeParams);
    $removeUrl = route($routePrefix . 'cart.remove', $routeParams);
    $checkoutSubmitUrl = route($routePrefix . 'checkout.submit', $routeParams);
    $checkPromoUrl = route($routePrefix . 'checkout.check-promo-conditions', $routeParams);
    $saveFormUrl = route($routePrefix . 'checkout.save-form-data', $routeParams, false);
    $paypartsOptionsUrl = route($routePrefix . 'checkout.payparts-options', $routeParams, false);
    $client    = auth()->user();
    // Показываем все сохранённые адреса; если координат нет, они будут дозапрошены и сохранены при выборе
    $addresses = $client ? $client->addresses()->orderByDesc('id')->get() : collect();

    // Загружаем данные из сессии
    $sessionData = $sessionData ?? session('checkout.form_data', []);

    // Определяем выбранный адрес и способ получения
    $selectedId = old('selected_address_id', $sessionData['selected_address_id'] ?? null) ?: ($addresses->first()->id ?? null);

    // Если у клиента НЕТ сохранённых адресов — всегда показываем форму нового адреса
    if (! $client || $addresses->count() === 0) {
        $selectedId    = null;
        $useNewInitial = true;
    } else {
        // Базовое значение из старых данных / сессии (если пользователь явно выбирал «новый адрес»)
        $useNewInitial = (bool) old('use_new_address', $sessionData['use_new_address'] ?? false);

        // Если у нас уже есть выбранный сохранённый адрес — по умолчанию НЕ открываем форму нового адреса
        if ($selectedId && $addresses->contains('id', $selectedId)) {
            $useNewInitial = false;
        }
    }
    $shippingMethod = old('shipping_method', $sessionData['shipping_method'] ?? 'delivery');
    $deliveryMode = old('delivery_mode', $sessionData['delivery_mode'] ?? 'asap');
    $paymentMethod = old('payment_method', 'liqpay');
    $defaultPaypartsBank = collect($paypartsBanks ?? [])->first();
    $defaultPaypartsPlan = data_get($defaultPaypartsBank, 'rules.0', []);
    $selectedPaypartsBankId = old('payparts_bank_id', $sessionData['payparts_bank_id'] ?? data_get($defaultPaypartsBank, 'id'));
    $selectedPaypartsPlanKey = old('payparts_plan_key', $sessionData['payparts_plan_key'] ?? data_get($defaultPaypartsPlan, 'key'));
@endphp

@section('content')
    <div class="mx-auto desk:w-[1208px] px-4  md:p-6 max-w-full">
        <h1 class="checkout-section-title mb-4 md:mb-6">{{ st('cart.miy-zakaz', 'Мій заказ') }}</h1>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('checkoutForm', () => ({
        method: @json($shippingMethod),
        useNew: @json($useNewInitial),
        deliveryMode: @json($deliveryMode),
        paymentMethod: @json($paymentMethod),
        paypartsBankId: @json((string) ($selectedPaypartsBankId ?? '')),
        paypartsPlanKey: @json((string) ($selectedPaypartsPlanKey ?? '')),
        showPaypartsTerms: false,
        paypartsTermsTitle: '',
        paypartsTermsHtml: '',
        closePaypartsTerms() {
            this.showPaypartsTerms = false;
            this.paypartsTermsTitle = '';
            this.paypartsTermsHtml = '';
        },
        syncPaypartsBankCards() {
            document.querySelectorAll('[data-payparts-bank-card]').forEach((card) => {
                const isActive = String(card.dataset.paypartsBankCard || '') === String(this.paypartsBankId || '');
                card.style.borderColor = isActive ? '#ff7500' : '#d8d8d8';
                card.style.boxShadow = isActive ? '0 0 0 1px rgba(255,117,0,.18)' : 'none';
            });
        },
        init() {
            window.addEventListener('open-payparts-terms', (event) => {
                const detail = event?.detail || {};
                this.paypartsTermsTitle = detail.title || '';
                this.paypartsTermsHtml = detail.html || '';
                this.showPaypartsTerms = true;
            });

            // Загружаем данные из сессии при инициализации после небольшой задержки
            this.$nextTick(() => {
                const sessionData = @json($sessionData ?? []);
                if (sessionData && Object.keys(sessionData).length > 0) {
                    try {
                        // Обновляем способ получения
                        if (sessionData.shipping_method) {
                            this.method = sessionData.shipping_method;
                        }
                        // Обновляем режим доставки
                        if (sessionData.delivery_mode) {
                            this.deliveryMode = sessionData.delivery_mode;
                        }
                        if (sessionData.payparts_bank_id) {
                            this.paypartsBankId = String(sessionData.payparts_bank_id);
                        }
                        if (sessionData.payparts_plan_key) {
                            this.paypartsPlanKey = String(sessionData.payparts_plan_key);
                        }
                    } catch (e) {
                        console.error('Error loading session data:', e);
                    }
                }

                if (this.paypartsBankId) {
                    const bankRadio = document.querySelector('[name="payparts_bank_id"][value="' + String(this.paypartsBankId).replace(/"/g, '&quot;') + '"]');
                    if (bankRadio) bankRadio.checked = true;
                }

                this.syncPaypartsBankCards();
            });
        }
    }));
});
</script>
@endpush

        <form action="{{ $checkoutSubmitUrl }}"
              method="POST" class="space-y-6" data-checkout-form novalidate
              data-check-promo-url="{{ $checkPromoUrl }}"
              data-payparts-options-url="{{ $paypartsOptionsUrl }}">
            @csrf

            <div
                x-data="checkoutForm"
                class="mb-6"
            >
                {{-- Переключатель способа получения + hidden
                --}}
                <div id="blk-toggle" class="mt-4 md:mt-6">
                    @include(front_view('checkout.partials._shipping-toggle'))
                </div>
                {{-- Весь блок внутри страницы "Мій заказ" --}}
                <div class="mt-4 md:mt-6 flex flex-col lg:flex-row justify-center gap-4 md:gap-6 lg:gap-[32px]">

                    {{-- Левая колонка (форма) --}}
                    <div class="w-full lg:w-[580px] space-y-4 md:space-y-6" id="col-left">

                        <div id="blk-contact">
                            @include(front_view('checkout.partials._contact'), ['sessionData' => $sessionData])
                        </div>

                        <div id="blk-address">
                            @include(front_view('checkout.partials._delivery-address'), [
                                'sessionData' => $sessionData,
                                'useNewInitial' => $useNewInitial,
                                'selectedId' => $selectedId
                            ])
                            @include(front_view('checkout.partials._pickup-locations'))
                        </div>

                        <div id="blk-extras">
                            @include(front_view('checkout.partials._extras'), ['sessionData' => $sessionData])
                        </div>

                        <div id="blk-conditions">
                            @include(front_view('checkout.partials._delivery-conditions'), [
                                'sessionData' => $sessionData,
                                'deliveryMode' => $deliveryMode,
                                'timeIntervals' => $timeIntervals ?? []
                            ])
                        </div>

                        {{-- Акции --}}
                        <div id="blk-promotions">
                            @include(front_view('checkout.partials._promotions'))
                        </div>

                        {{-- Способ оплаты --}}
                        <div id="blk-pay">
                            @include(front_view('checkout.partials._payment-methods'), [
                                'sessionData' => $sessionData,
                                'paymentMethod' => $paymentMethod
                            ])
                        </div>

                    </div>

                    {{-- Правая колонка (корзина+итоги) --}}
                    <div class="w-full lg:w-[585px] space-y-4 md:space-y-6" id="col-right">

                        <div id="blk-items">
                            @include(front_view('checkout.partials._order-items'))
                        </div>

                        {{-- Промокод --}}
                        <div id="blk-promocode">
                            @include(front_view('checkout.partials._summary-promo'))
                        </div>

                        {{-- Бонусы --}}
                        <div id="blk-bonus">
                            @include(front_view('checkout.partials._summary-bonus'))
                        </div>

                        {{-- Сумма --}}
                        <div id="blk-totals">
                            @include(front_view('checkout.partials._summary-totals'))
                        </div>

                        {{-- Согласие + кнопка --}}
                        <div id="blk-submit">
                            @include(front_view('checkout.partials._summary-submit'))
                        </div>
                        <div id="blk-earned">
                            @include(front_view('checkout.partials._bonus-earned'))
                        </div>
                    </div>

                </div>

                <style>
                    [x-cloak] { display: none !important; }
                </style>

                <div
                    x-cloak
                    x-show="showPaypartsTerms"
                    x-transition.opacity
                    class="fixed inset-0 z-[10000] flex items-start justify-center overflow-y-auto bg-black/50 px-4 pt-[180px] pb-12 md:pt-[170px]"
                    @keydown.escape.window="closePaypartsTerms()"
                    @click.self="closePaypartsTerms()"
                >
                    <div class="relative w-full max-w-[720px] max-h-[calc(100vh-14rem)] overflow-y-auto rounded-lg bg-white px-6 py-6 shadow-xl md:max-h-[calc(100vh-13rem)]">
                        <button type="button" class="absolute right-4 top-4 flex h-12 w-12 items-center justify-center rounded-full hover:bg-gray-100" @click="closePaypartsTerms()">
                            <span class="sr-only">Закрити</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-700" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </button>

                        <div class="pr-10">
                            <div class="text-lg font-semibold text-[#272828]" x-text="paypartsTermsTitle"></div>
                        </div>

                        <div class="mt-5 space-y-5 text-sm text-gray-700">
                            <template x-if="paypartsTermsHtml">
                                <div>
                                    <div class="mb-2 text-base font-semibold text-[#272828]">{{ html_entity_decode(st('cart.payment.credit_terms', '&#1059;&#1084;&#1086;&#1074;&#1080; &#1082;&#1088;&#1077;&#1076;&#1080;&#1090;&#1091;&#1074;&#1072;&#1085;&#1085;&#1103;'), ENT_QUOTES, 'UTF-8') }}</div>
                                    <div class="prose max-w-none leading-6" x-html="paypartsTermsHtml"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

        </form>
        <div x-data="{ showAuthModal: false, authMessage: '' }"
             x-cloak
             x-show="showAuthModal"
             x-on:show-auth-modal.window="
        authMessage = $event.detail.message || 'Щоб застосувати акцію, увійдіть або зареєструйтесь.';
           authName   = $event.detail.name  || '';
        authPhone  = $event.detail.phone || '';
        showAuthModal = true;
     "
             x-transition.opacity
             class="fixed inset-0 z-[500] flex items-center justify-center bg-black/50 backdrop-blur-sm">

            <div x-show="showAuthModal"
                 x-transition.scale.80
                 class="bg-white rounded-2xl shadow-xl p-6 w-[90%] max-w-[380px] text-center">

                <div class="text-lg font-semibold mb-3">{{ st('cart.potribna-avtoryzatsiya', 'Потрібна авторизація') }}</div>
                <div class="text-sm text-gray-700 mb-6" x-text="authMessage"></div>

                <div class="flex justify-center gap-3">
                    <button
                        type="button"
                        class="h-[40px] w-full rounded-full bg-[#FF7500] text-white
           text-[14px] font-semibold hover:bg-[#e56700] transition"
                        @click="
        // 1) закрываем эту модалку
        showAuthModal = false;

        // 2) подтягиваем имя, телефон и email из формы чекаута (если заполнены)
        const authName  = document.getElementById('contact_name')?.value || '';
        const authPhone = document.getElementById('contact_phone')?.value || '';
        const authEmail = document.getElementById('contact_email')?.value || '';

        // 3) открываем основное окно авторизации с уже подставленными данными
        $dispatch('open-auth-modal', {
            tab: 'login',
            name: authName,
            phone: authPhone,
            email: authEmail,
        });
    "
                    >
                        <span>{{ st('auth.login','Увійти') }}</span>
                    </button>




                    <button type="button"
                            @click="showAuthModal = false"
                            class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">
                        {{ st('all.skasuvaty','Скасувати') }}
                    </button>
                </div>
            </div>
        </div>


    </div>
@endsection
@php
    $zones = \App\Models\DeliveryZone::where('is_active', true)
        ->orderBy('sort_order')
        ->get()
        ->keyBy('name')
        ->map(function($zone) {
            return [
                'name' => $zone->name,
                'color' => $zone->color,
                'delivery_price' => (float)$zone->delivery_price,
                'delivery_time_min' => (int)$zone->delivery_time_min,
                'delivery_time_max' => (int)$zone->delivery_time_max,
                'free_delivery_from' => (float)$zone->free_delivery_from,
            ];
        });
@endphp

@push('scripts')
    <script>
        window.DELIVERY_ZONES = @json($zones);
    </script>

    <script>
        (function() {
            window.__googleMapsLoading = true;
            window.__googleMapsLoaded = false;
            window.__onGoogleMapsLoaded = function() {
                window.__googleMapsLoaded = true;
                window.__googleMapsLoading = false;
            };

            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places,geometry&callback=__onGoogleMapsLoaded';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        })();
    </script>

    @vite(['resources/js/map-cart.js'])
    @push('scripts')
        <script>
            // Используем относительный URL, чтобы на клон‑сайтах (test-домены, другие хостинги)
            // запрос шёл на тот же домен, с которого открыт checkout.
            window.CHECKOUT_CONFIG = {
                csrf: @json(csrf_token()),
                // route(..., [], false) — путь без домена, например "/checkout/save-form-data"
                saveUrl: @json($saveFormUrl),
                googleMapsKey: @json(config('services.google_maps.key')),
                scheduleV2: @json($scheduleV2 ?? ['enabled' => false]),
            };
        </script>
    @endpush

@endpush
