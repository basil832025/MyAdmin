<div class="bg-white rounded shadow-[0_2px_10px_rgba(0,0,0,.08)] pt-3 pr-4 pb-3 pl-4">
    <div class="checkout-section-title mb-3 md:mb-4">
        {{ st('cart.payment.title', 'Способи оплати') }}
    </div>

    @php
        $paypartsBanks = collect($paypartsBanks ?? []);
        $defaultPaypartsBank = $paypartsBanks->first();
        $defaultPaypartsPlan = data_get($defaultPaypartsBank, 'rules.0', []);
        $selectedPaypartsBankId = old('payparts_bank_id', $sessionData['payparts_bank_id'] ?? data_get($defaultPaypartsBank, 'id'));
        $selectedPaypartsPlanKey = old('payparts_plan_key', $sessionData['payparts_plan_key'] ?? data_get($defaultPaypartsPlan, 'key'));
        $availablePaypartsPlanKeys = $paypartsBanks
            ->flatMap(fn ($bank) => collect(data_get($bank, 'rules', []))->pluck('key'))
            ->map(fn ($key) => (string) $key)
            ->values();
        $availablePaypartsBankIds = $paypartsBanks
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($availablePaypartsPlanKeys->isNotEmpty() && ! $availablePaypartsPlanKeys->contains((string) $selectedPaypartsPlanKey)) {
            $selectedPaypartsPlanKey = (string) data_get($defaultPaypartsPlan, 'key', '');
        }

        $paypartsFinancialPhone = old('payparts_financial_phone', $sessionData['payparts_financial_phone'] ?? auth()->user()?->phone ?? $sessionData['contact_phone'] ?? '');
        $paypartsFinancialPhoneDigits = preg_replace('/\D+/', '', (string) $paypartsFinancialPhone);

        if (str_starts_with($paypartsFinancialPhoneDigits, '380')) {
            $paypartsFinancialPhoneDigits = substr($paypartsFinancialPhoneDigits, 3);
        } elseif (str_starts_with($paypartsFinancialPhoneDigits, '38')) {
            $paypartsFinancialPhoneDigits = substr($paypartsFinancialPhoneDigits, 2);
        } elseif (str_starts_with($paypartsFinancialPhoneDigits, '0')) {
            $paypartsFinancialPhoneDigits = substr($paypartsFinancialPhoneDigits, 1);
        }

        $paypartsFinancialPhoneDigits = substr($paypartsFinancialPhoneDigits, 0, 9);
        $paypartsFinancialPhoneDisplay = trim(implode(' ', array_filter([
            substr($paypartsFinancialPhoneDigits, 0, 2),
            substr($paypartsFinancialPhoneDigits, 2, 3),
            substr($paypartsFinancialPhoneDigits, 5, 2),
            substr($paypartsFinancialPhoneDigits, 7, 2),
        ], static fn ($part) => $part !== '')));
    @endphp

    <div class="flex flex-col gap-6">
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="payment_method" value="liqpay" class="tp-radio" x-model="paymentMethod" @checked($paymentMethod === 'liqpay')>
            <span class="flex items-center gap-3 text-[16px] leading-[22px] text-[#272828]">
                <span>
                    <svg width="21" height="17" viewBox="0 0 21 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="21" height="16.5" rx="4" fill="#FF7500"/>
                        <path d="M6.175 3L5 4.175L8.81667 8L5 11.825L6.175 13L11.175 8L6.175 3Z" fill="white"/>
                        <path d="M12.3508 3L11.1758 4.175L14.9924 8L11.1758 11.825L12.3508 13L17.3508 8L12.3508 3Z" fill="white"/>
                    </svg>
                </span>
                {{ st('cart.payment.liqpay', 'Онлайн-оплата карткою') }}
            </span>
        </label>
        <p class="text-xs text-gray-500 pl-8 -mt-4">
            {{ st('cart.payment.liqpay_note', 'Переадресуємо на захищену сторінку LiqPay. Ми не обробляємо дані вашої картки.') }}
        </p>

        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="payment_method" value="card_on_delivery" class="tp-radio" x-model="paymentMethod" @checked($paymentMethod === 'card_on_delivery')>
            <span class="flex items-center gap-3 text-[16px] leading-[22px] text-[#272828]">
                <span class="text-[#FF7500]" aria-hidden="true">
                    <svg width="21" height="17" viewBox="0 0 21 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 13.875C0 14.5712 0.276562 15.2389 0.768845 15.7312C1.26113 16.2234 1.92881 16.5 2.625 16.5H18.375C19.0712 16.5 19.7389 16.2234 20.2312 15.7312C20.7234 15.2389 21 14.5712 21 13.875V6.65625H0V13.875ZM3.09375 10.3125C3.09375 9.93954 3.24191 9.58185 3.50563 9.31813C3.76935 9.05441 4.12704 8.90625 4.5 8.90625H6.75C7.12296 8.90625 7.48065 9.05441 7.74437 9.31813C8.00809 9.58185 8.15625 9.93954 8.15625 10.3125V11.25C8.15625 11.623 8.00809 11.9806 7.74437 12.2444C7.48065 12.5081 7.12296 12.6562 6.75 12.6562H4.5C4.12704 12.6562 3.76935 12.5081 3.50563 12.2444C3.24191 11.9806 3.09375 11.623 3.09375 11.25V10.3125ZM18.375 0H2.625C1.92881 0 1.26113 0.276562 0.768845 0.768845C0.276562 1.26113 0 1.92881 0 2.625V3.84375H21V2.625C21 1.92881 20.7234 1.26113 20.2312 0.768845C19.7389 0.276562 19.0712 0 18.375 0Z" fill="#FF7500"/>
                    </svg>
                </span>
                {{ st('cart.payment.card_on_delivery', 'Оплата через POS-термінал при отриманні') }}
            </span>
        </label>

        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="payment_method" value="cash" class="tp-radio" x-model="paymentMethod" @checked($paymentMethod === 'cash')>
            <span class="flex items-center gap-3 text-[16px] leading-[22px] text-[#272828]">
                <span class="text-[#FF7500]" aria-hidden="true">
                    <svg width="21" height="19" viewBox="0 0 21 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.96 6.66708H18.6667V3.33375C18.6667 3.15694 18.5964 2.98737 18.4714 2.86235C18.3464 2.73732 18.1768 2.66708 18 2.66708H2C1.82319 2.66708 1.65362 2.59685 1.5286 2.47182C1.40357 2.3468 1.33333 2.17723 1.33333 2.00042C1.33333 1.82361 1.40357 1.65404 1.5286 1.52901C1.65362 1.40399 1.82319 1.33375 2 1.33375H17.7333C17.9101 1.33375 18.0797 1.26351 18.2047 1.13849C18.3298 1.01346 18.4 0.843894 18.4 0.667083C18.4 0.490272 18.3298 0.320703 18.2047 0.195679C18.0797 0.0706545 17.9101 0.000416435 17.7333 0.000416435H2C1.7426 -0.00489035 1.48667 0.040568 1.24683 0.134195C1.007 0.227821 0.787966 0.367782 0.602238 0.546081C0.41651 0.72438 0.267729 0.937523 0.164396 1.17334C0.0610619 1.40915 0.00519958 1.66301 0 1.92042V15.9204C0.000872594 16.2826 0.073176 16.641 0.212769 16.9751C0.352362 17.3093 0.556503 17.6126 0.8135 17.8677C1.0705 18.1229 1.3753 18.3248 1.71046 18.462C2.04561 18.5991 2.40453 18.6688 2.76667 18.6671H18C18.1768 18.6671 18.3464 18.5968 18.4714 18.4718C18.5964 18.3468 18.6667 18.1772 18.6667 18.0004V14.6671H19.96C20.0441 14.6734 20.1287 14.6626 20.2085 14.6351C20.2883 14.6077 20.3616 14.5642 20.4241 14.5074C20.4865 14.4506 20.5366 14.3817 20.5715 14.3048C20.6063 14.228 20.6251 14.1448 20.6267 14.0604V7.39375C20.6289 7.21054 20.5611 7.03338 20.4373 6.89837C20.3134 6.76335 20.1427 6.68064 19.96 6.66708ZM19.3333 13.3337H13.6133C12.9301 13.3094 12.2845 13.0149 11.8182 12.5149C11.352 12.0149 11.1033 11.3503 11.1267 10.6671C11.1033 9.98384 11.352 9.31923 11.8182 8.81924C12.2845 8.31925 12.9301 8.02475 13.6133 8.00042H19.3333V13.3337Z" fill="#FF7500"/>
                    </svg>
                </span>
                {{ st('cart.payment.cash', 'Готівкою при отриманні') }}
            </span>
        </label>

        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="payment_method" value="invoice" class="tp-radio" x-model="paymentMethod" @checked($paymentMethod === 'invoice')>
            <span class="flex items-center gap-3 text-[16px] leading-[22px] text-[#272828]">
                <span class="text-[#FF7500]" aria-hidden="true">
                    <svg width="21" height="19" viewBox="0 0 21 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.6667 0H2.33333C1.04467 0 0 1.04467 0 2.33333V16.6667C0 17.9553 1.04467 19 2.33333 19H18.6667C19.9553 19 21 17.9553 21 16.6667V2.33333C21 1.04467 19.9553 0 18.6667 0ZM18.6667 16.6667H2.33333V2.33333H18.6667V16.6667ZM15.1667 4.66667H5.83333V7H15.1667V4.66667ZM13.4167 8.5H5.83333V10.8333H13.4167V8.5ZM13.4167 12.3333H5.83333V14.6667H13.4167V12.3333Z" fill="#FF7500"/>
                    </svg>
                </span>
                {{ st('cart.payment.invoice', 'Безготівковий розрахунок за рахунком для юридичних осіб') }}
            </span>
        </label>

        @if($paypartsEnabled)
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="payment_method" value="payparts" class="tp-radio" x-model="paymentMethod" @checked($paymentMethod === 'payparts')>
                <span class="flex items-center gap-3 text-[16px] leading-[22px] text-[#272828]">
                    <span class="text-[#FF7500]" aria-hidden="true">
                        <img src="{{ asset('images/svg/pie_icon_from_image.svg') }}?v=3" width="25" height="25" class="block bg-transparent" alt="">
                    </span>
                    {{ st('cart.payment.payparts_type_pp', 'Оплата частинами') }}
                    <img src="{{ asset('images/payments/payparts-badge.png') }}" width="46" height="25" class="block shrink-0" alt="" aria-hidden="true">
                </span>
            </label>

            <div
                x-show="paymentMethod === 'payparts'"
                x-cloak
                data-default-payparts-bank-id="{{ (string) $selectedPaypartsBankId }}"
                data-default-payparts-plan-key="{{ (string) $selectedPaypartsPlanKey }}"
                data-available-payparts-bank-ids='@json($availablePaypartsBankIds)'
                data-available-payparts-plan-keys='@json($availablePaypartsPlanKeys)'
                x-init="$nextTick(() => {
                    const bankIds = JSON.parse($el.dataset.availablePaypartsBankIds || '[]');
                    const planKeys = JSON.parse($el.dataset.availablePaypartsPlanKeys || '[]');
                    if (!paypartsBankId || (bankIds.length && !bankIds.includes(String(paypartsBankId)))) paypartsBankId = $el.dataset.defaultPaypartsBankId || '';
                    if (!paypartsPlanKey || !planKeys.includes(String(paypartsPlanKey))) paypartsPlanKey = $el.dataset.defaultPaypartsPlanKey || '';
                })"
                class="pl-8 space-y-3"
            >


                @if($paypartsBanks->isEmpty())
                    <p class="text-sm text-gray-500">
                        {{ st('cart.payment.payparts_unavailable', 'Оплата частинами зараз налаштовується. Доступні банки зʼявляться після додавання записів у адмінці.') }}
                    </p>
                @else
                    <div class="grid gap-5 pt-4 md:grid-cols-2">
                        @foreach($paypartsBanks as $bank)
                            @php
                                $bankPlans = collect($bank['rules'] ?? []);
                                $bankPlanKeys = $bankPlans->pluck('key')->map(fn ($key) => (string) $key)->values();
                                $defaultBankPlanKey = (string) ($bankPlanKeys->first() ?? '');
                                $plansByType = $bankPlans->groupBy('merchant_type');
                            @endphp
                            <div
                                data-payparts-bank-card="{{ $bank['id'] }}"
                                class="relative rounded border bg-white px-4 pb-5 pt-5 transition md:min-h-[620px]"
                                style="border-color: {{ (string) $selectedPaypartsBankId === (string) $bank['id'] ? '#ff7500' : '#d8d8d8' }}; {{ (string) $selectedPaypartsBankId === (string) $bank['id'] ? 'box-shadow: 0 0 0 1px rgba(255,117,0,.18);' : '' }}"
                                :style="String(paypartsBankId) === '{{ $bank['id'] }}' ? 'border-color: #ff7500; box-shadow: 0 0 0 1px rgba(255,117,0,.18);' : 'border-color: #d8d8d8; box-shadow: none;'"
                                @click="paypartsBankId = '{{ $bank['id'] }}'; if (!@js($bankPlanKeys->all()).includes(paypartsPlanKey)) paypartsPlanKey = @js($defaultBankPlanKey)"
                                @if($loop->first)
                                    x-init="$nextTick(() => { if (!paypartsBankId) paypartsBankId = '{{ $bank['id'] }}'; if (!paypartsPlanKey) paypartsPlanKey = @js($defaultBankPlanKey); })"
                                @endif
                            >

                                <label class="block cursor-pointer">
                                    <input
                                        type="radio"
                                        name="payparts_bank_id"
                                        value="{{ $bank['id'] }}"
                                        class="tp-check-radio absolute left-1/2 top-0 z-10 block -translate-x-1/2 -translate-y-1/2 cursor-pointer"
                                        x-model="paypartsBankId"
                                        @change="paypartsBankId = '{{ $bank['id'] }}'; if (!@js($bankPlanKeys->all()).includes(paypartsPlanKey)) paypartsPlanKey = @js($defaultBankPlanKey); syncPaypartsBankCards()"
                                        @checked((string) $selectedPaypartsBankId === (string) $bank['id'])
                                    >
                                    <div class="flex min-w-0 items-center gap-3">
                                        @if(($bank['bank_type'] ?? null) === 'privatbank')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 shrink-0" width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                                                <g clip-path="url(#privatbank-logo-{{ $bank['id'] }})">
                                                    <path d="M32.7059 32H20.6827C20.6827 31.029 20.6922 30.0756 20.6803 29.1211C20.6637 27.7979 20.4866 26.4969 20.0541 25.2395C19.075 22.3911 17.0156 20.7497 14.1114 20.0511C12.5999 19.6871 11.0611 19.6636 9.51867 19.6707C9.0279 19.673 8.53713 19.6707 8.03448 19.6707V8H32.7059V32ZM27.5986 27.0406V12.9771H13.1525V14.8521C20.3809 15.8595 24.6136 19.8421 25.6557 27.0417H27.5998L27.5986 27.0406Z" fill="#76AE42"/>
                                                    <path d="M8 31.9905V22.6246H17.649V31.9905H8Z" fill="black"/>
                                                </g>
                                                <defs>
                                                    <clipPath id="privatbank-logo-{{ $bank['id'] }}">
                                                        <rect width="24.7059" height="24" fill="white" transform="translate(8 8)"/>
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                        @else
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#242938] text-sm font-semibold text-white">
                                                {{ mb_strtoupper(mb_substr((string) ($bank['bank_label'] ?? $bank['bank_type']), 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="truncate text-[16px] font-normal leading-[22px] text-[#272828]">
                                                {{ $bank['name'] ?? $bank['bank_label'] ?? $bank['bank_type'] }}
                                            </div>
                                            <button
                                                type="button"
                                                class="mt-3 text-[14px] leading-[18px] hover:underline"
                                                style="color: #4ba3df;"
                                                onmouseenter="this.style.color = '#2384c6'"
                                                onmouseleave="this.style.color = '#4ba3df'"
                                                onclick="window.dispatchEvent(new CustomEvent('open-payparts-terms', { detail: { title: @js($bank['name'] ?? $bank['bank_label'] ?? $bank['bank_type']), html: @js((string) ($bank['terms'] ?? '')) } })); return false;"
                                            >
                                                {{ st('cart.payment.read_terms', 'Читати умови') }}
                                            </button>
                                        </div>
                                    </div>
                                </label>

                                <div class="mt-5 space-y-5">
                                    @if($bankPlans->isEmpty())
                                        <p class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-[14px] leading-[20px] text-amber-700">
                                            {{ st('cart.payment.payparts_no_rules', 'Для цієї суми немає доступних умов кредитування.') }}
                                            @if(!empty($bank['min_amount']))
                                                <br>
                                                {{ st('cart.payment.payparts_min_amount_hint', 'Мінімальна сума для оплати частинами') }}:
                                                <strong>{{ number_format((float) $bank['min_amount'], 0, ',', ' ') }} {{ st('cart.summary.currency_short', 'грн') }}</strong>
                                            @endif
                                        </p>
                                    @else
                                        <div>
                                            <div class="mb-3 text-[15px] font-semibold leading-[20px] text-black">
                                                {{ st('cart.payment.credit_type', 'Умова кредиту') }}
                                            </div>
                                            <div class="space-y-3">
                                                @foreach($plansByType as $merchantType => $plans)
                                                    @php
                                                        $typePlanKeys = $plans->pluck('key')->map(fn ($key) => (string) $key)->values();
                                                        $firstTypePlan = $plans->first();
                                                        $firstTypePlanKey = (string) data_get($firstTypePlan, 'key');
                                                    @endphp
                                                    <label class="flex cursor-pointer items-center gap-3 text-[14px] leading-[20px] text-[#272828]">
                                                        <input
                                                            type="radio"
                                                            name="payparts_merchant_type_{{ $bank['id'] }}"
                                                            value="{{ $merchantType }}"
                                                            class="tp-radio"
                                                            :checked="@js($typePlanKeys->all()).includes(paypartsPlanKey)"
                                                            @change="paypartsBankId = '{{ $bank['id'] }}'; paypartsPlanKey = @js($firstTypePlanKey)"
                                                        >
                                                        <span>{{ data_get($firstTypePlan, 'merchant_type_label', $merchantType) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        @foreach($plansByType as $merchantType => $plans)
                                            @php
                                                $typePlanKeys = $plans->pluck('key')->map(fn ($key) => (string) $key)->values();
                                            @endphp
                                            <div x-show="@js($typePlanKeys->all()).includes(paypartsPlanKey)" x-cloak>
                                                <label class="mb-2 block text-[15px] font-semibold leading-[20px] text-black" for="payparts-plan-{{ $bank['id'] }}-{{ $merchantType }}">
                                                    {{ st('cart.payment.credit_term', 'Строк кредиту') }}
                                                </label>
                                                <select
                                                    id="payparts-plan-{{ $bank['id'] }}-{{ $merchantType }}"
                                                    name="payparts_plan_key"
                                                    class="h-9 w-full rounded border border-[#dadada] bg-white px-3 text-[14px] leading-[18px] text-black focus:border-[#4f8fe8] focus:outline-none"
                                                    x-model="paypartsPlanKey"
                                                    :disabled="String(paypartsBankId) !== '{{ $bank['id'] }}' || !@js($typePlanKeys->all()).includes(paypartsPlanKey)"
                                                    @change="paypartsBankId = '{{ $bank['id'] }}'"
                                                >
                                                    @foreach($plans as $plan)
                                                        <option value="{{ $plan['key'] }}">
                                                            {{ $plan['parts_count'] }} {{ st('cart.payment.payments_count', 'платежів') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach

                                         @foreach($bankPlans as $plan)
                                             <div
                                                 x-show="paypartsPlanKey === @js((string) $plan['key'])"
                                                x-cloak
                                                class="space-y-5 pt-1"
                                            >
                                                <div>
                                                    <div class="text-[15px] font-semibold leading-[20px] text-black">{{ st('cart.payment.monthly_payment', 'Щомісяця') }}</div>
                                                    <div class="mt-2 text-[15px] leading-[20px] text-[#00a000]">{{ $plan['formatted_monthly_amount'] }} {{ st('cart.summary.currency_short', 'грн') }}</div>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2 text-[15px] font-semibold leading-[20px] text-black">
                                                        <span>{{ st('cart.payment.credit_total', 'Вартість кредиту') }}</span>
                                                        <span
                                                            class="relative"
                                                            x-data="{
                                                                open: false,
                                                                style: '',
                                                                id: Math.random().toString(36).slice(2),
                                                                update() {
                                                                    if (!this.open) return;

                                                                    this.$nextTick(() => {
                                                                        const btn = this.$refs.btn;
                                                                        const tip = this.$refs.tip;
                                                                        if (!btn || !tip) return;

                                                                        tip.style.visibility = 'hidden';
                                                                        tip.style.display = 'block';

                                                                        const br = btn.getBoundingClientRect();
                                                                        const tr = tip.getBoundingClientRect();
                                                                        const marginLeft = 8;
                                                                        const marginRight = 20;
                                                                        const bottomSafe = 40;

                                                                        let left = br.left + br.width / 2 - tr.width / 2;
                                                                        left = Math.max(marginLeft, Math.min(left, window.innerWidth - tr.width - marginRight));

                                                                        let top = br.bottom + 8;
                                                                        if (top + tr.height + bottomSafe > window.innerHeight) {
                                                                            top = window.innerHeight - tr.height - bottomSafe;
                                                                        }
                                                                        top = Math.max(marginLeft, top);

                                                                        this.style = `left:${left}px; top:${top}px;`;

                                                                        tip.style.display = '';
                                                                        tip.style.visibility = '';
                                                                    });
                                                                },
                                                                handleOpenEvent(event) {
                                                                    if (event.detail !== this.id) {
                                                                        this.open = false;
                                                                    }
                                                                }
                                                            }"
                                                            x-init="
                                                                window.addEventListener('resize', () => update());
                                                                window.addEventListener('scroll', () => update(), true);
                                                                window.addEventListener('payparts-credit-tooltip-open', (event) => handleOpenEvent(event));
                                                            "
                                                        >
                                                            <button
                                                                type="button"
                                                                x-ref="btn"
                                                                class="flex h-5 w-5 items-center justify-center rounded-full border border-[#d8d8d8] text-[12px] font-normal text-[#555] focus:outline-none"
                                                                @click.stop="
                                                                    open = !open;
                                                                    if (open) {
                                                                        window.dispatchEvent(new CustomEvent('payparts-credit-tooltip-open', { detail: id }));
                                                                        update();
                                                                    }
                                                                "
                                                                aria-label="{{ st('cart.payment.credit_total_help', 'Довідка про вартість кредиту') }}"
                                                            >
                                                                ?
                                                            </button>

                                                            <div
                                                                x-ref="tip"
                                                                x-show="open"
                                                                x-transition
                                                                x-cloak
                                                                @click.outside="open = false"
                                                                :style="style"
                                                                class="fixed z-50 max-h-[calc(100vh-32px)] w-64 max-w-[calc(100vw-16px)] overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 text-xs font-normal leading-5 text-gray-700 shadow-lg"
                                                            >
                                                                <div class="mb-1 flex justify-end">
                                                                    <button
                                                                        type="button"
                                                                        class="inline-flex h-5 w-5 items-center justify-center text-gray-500 hover:text-gray-700"
                                                                        @click.stop="open = false"
                                                                        aria-label="{{ st('all.close', 'Закрити') }}"
                                                                    >
                                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                                            <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                                <div>
                                                                    {{ st('cart.payment.credit_total_tooltip', 'Фактическая стоимость будет определена при оформлении чек-договора с Банком согласно выбранных условий.') }}
                                                                </div>
                                                            </div>
                                                        </span>
                                                    </div>
                                                    <div class="mt-2 text-[15px] leading-[20px] text-[#00a000]">{{ $plan['formatted_amount'] }} {{ st('cart.summary.currency_short', 'грн') }}</div>
                                                </div>
                                                <div class="text-[15px] leading-[20px] text-black">
                                                    <span class="font-semibold">{{ st('cart.payment.credit_percent', '% по кредиту') }}:</span>
                                                    <span>{{ $plan['formatted_interest_amount'] }} {{ st('cart.summary.currency_short', 'грн') }}</span>
                                                 </div>
                                             </div>
                                         @endforeach

                                         @if(!empty($bank['description']))
                                             <div class="prose max-w-none rounded border bg-[#f4fbef] px-3 py-3 text-[14px] leading-[20px] text-[#2f5f1d]" style="border-color: #76AE42;">
                                                 {!! $bank['description'] !!}
                                             </div>
                                         @endif

                                         <div
                                             x-data="{
                                                 financialPhoneDigits: @js($paypartsFinancialPhoneDigits),
                                                 formatFinancialPhone(value) {
                                                     let digits = String(value || '').replace(/\D/g, '').replace(/^0+/, '').slice(0, 9);
                                                     return [
                                                         digits.slice(0, 2),
                                                         digits.slice(2, 5),
                                                         digits.slice(5, 7),
                                                         digits.slice(7, 9)
                                                     ].filter(Boolean).join(' ');
                                                 },
                                                 syncFinancialPhone(event) {
                                                     let digits = String(event.target.value || '').replace(/\D/g, '').replace(/^0+/, '').slice(0, 9);
                                                     this.financialPhoneDigits = digits;
                                                     event.target.value = this.formatFinancialPhone(digits);
                                                 }
                                             }"
                                             x-init="$nextTick(() => {
                                                 if ($refs.paypartsFinancialPhoneInput) {
                                                     $refs.paypartsFinancialPhoneInput.value = formatFinancialPhone(financialPhoneDigits);
                                                 }
                                             })"
                                         >
                                             <label class="mb-2 block text-[15px] font-semibold leading-[20px] text-black" for="payparts-financial-phone-{{ $bank['id'] }}">
                                                 {{ st('cart.payment.payparts_financial_phone', 'Фінансовий номер телефону') }}
                                             </label>
                                             <input
                                                 type="hidden"
                                                 name="payparts_financial_phone"
                                                 :value="financialPhoneDigits.length === 9 ? '380' + financialPhoneDigits : ''"
                                                 :disabled="paymentMethod !== 'payparts' || String(paypartsBankId) !== '{{ $bank['id'] }}'"
                                             >
                                             <div class="flex max-w-[260px]">
                                                 <div class="flex h-10 shrink-0 items-center rounded-l border border-r-0 border-[#dadada] bg-gray-50 px-3 text-[14px] font-medium leading-[18px] text-black">
                                                     +380
                                                 </div>
                                                 <div class="relative flex-1">
                                                     <input
                                                         id="payparts-financial-phone-{{ $bank['id'] }}"
                                                         x-ref="paypartsFinancialPhoneInput"
                                                         type="tel"
                                                         value="{{ $paypartsFinancialPhoneDisplay }}"
                                                          class="h-10 w-full rounded-r border border-[#dadada] bg-white px-3 text-[16px] leading-[18px] text-black transition-colors focus:border-[#FF7500] focus:outline-none disabled:bg-gray-100 disabled:text-gray-500"
                                                         placeholder="__ ___ __ __"
                                                         inputmode="numeric"
                                                         dir="ltr"
                                                         autocomplete="tel-national"
                                                         pattern="[0-9]{2} [0-9]{3} [0-9]{2} [0-9]{2}"
                                                         title="{{ st('cart.payment.payparts_financial_phone_format', 'Введіть повний номер телефону') }}"
                                                         required
                                                         :disabled="paymentMethod !== 'payparts' || String(paypartsBankId) !== '{{ $bank['id'] }}'"
                                                         @input="syncFinancialPhone($event)"
                                                         @blur="$event.target.value = formatFinancialPhone(financialPhoneDigits)"
                                                     >
                                                 </div>
                                             </div>
                                         </div>
                                     @endif
                                 </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @error('payment_method')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
        @error('payparts_bank_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
        @error('payparts_plan_key')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
        @error('payparts_financial_phone')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>
