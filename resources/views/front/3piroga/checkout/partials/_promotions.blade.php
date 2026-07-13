@php
    $selectedPromo = old('selected_promo', session('checkout.selected_promo', 'none'));
    $locale = app()->getLocale();
    $isLocalized = in_array($locale, ['ru', 'en'], true);
    $promoUrl = $isLocalized
        ? route('localized.checkout.promo', ['locale' => $locale])
        : route('checkout.promo');
@endphp

@if($availablePromos->isNotEmpty())
    <div
        x-data="availablePromosComponent('{{ $selectedPromo }}')"
        class="bg-white rounded shadow-[0_2px_10px_rgba(0,0,0,.08)] pt-3 pr-4 pb-3 pl-4 relative"
    >
        <div class="checkout-section-title mb-3 md:mb-4">{{ st('cart.dostupni-aktsii', 'Доступні акції') }}</div>

        {{-- скрытое поле для отправки на submit --}}
        <input type="hidden" name="selected_promo" x-model="selected">

        <div class="space-y-3">
            {{-- Без акции --}}
            <label class="flex items-start gap-2 cursor-pointer relative group">
                <input type="radio"
                       class="tp-radio mt-[3px] shrink-0"
                       name="promo_radio"
                       value="none"
                       @change="change('none')"
                       :checked="selected === 'none'">
                <span class="text-[16px] leading-[22px] text-[#272828]">
                    {{ st('cart.bez-aktsii', 'Без акції') }}
                </span>
            </label>

            {{-- Список доступных акций --}}
            @foreach($availablePromos as $promo)
                @php
                    $value = $promo['type'] . ':' . $promo['id'];
                    $isActive = $promo['is_active'] ?? true;
                @endphp

                <label
                    x-data="{ promoActive: @js($isActive) }"
                    data-promo-value="{{ $value }}"
                    :class="promoActive ? 'cursor-pointer' : 'cursor-not-allowed'"
                    class="flex items-start gap-2 relative group"
                >
                    <input type="radio"
                           class="tp-radio mt-[3px] shrink-0"
                           :class="promoActive ? '' : 'opacity-50 cursor-not-allowed'"
                           name="promo_radio"
                           value="{{ $value }}"
                           :disabled="!promoActive"
                           @change="change('{{ $value }}')"
                           :checked="selected === '{{ $value }}'">

                    <span class="leading-5 flex items-center">
                        <span class="text-[16px] leading-[22px]" :class="promoActive ? 'text-[#272828]' : 'text-gray-400'">
                            {{ $promo['label'] }}
                        </span>

                        @if(!empty($promo['description']))
                            <span
                                class="ml-2 pointer-events-auto"
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

                // делаем видимым для корректного измерения
                tip.style.visibility = 'hidden';
                tip.style.display = 'block';

                const br = btn.getBoundingClientRect();
                const tr = tip.getBoundingClientRect();

                const marginLeft = 8;   // минимальный отступ слева
                const marginRight = 20; // минимальный отступ от правого края
                const bottomSafe = 40; // дополнительный отступ от нижнего края для мобильных

                let left = br.left + br.width / 2 - tr.width / 2;

                // clamp по X внутри viewport
                left = Math.max(
                    marginLeft,
                    Math.min(left, window.innerWidth - tr.width - marginRight)
                );

                // позиция по Y: под кнопкой
                let top = br.bottom + 8;
                const viewportHeight = window.innerHeight;

                // если тултип не влезает вниз — поднимаем его выше,
                // оставляя дополнительное место снизу (bottomSafe)
                if (top + tr.height + bottomSafe > viewportHeight) {
                    top = viewportHeight - tr.height - bottomSafe;
                }

                // и не даём уйти выше верхнего края
                top = Math.max(marginLeft, top);

                this.style = `left:${left}px; top:${top}px;`;

                tip.style.display = '';
                tip.style.visibility = '';
            });
        },
        handleOpenEvent(event) {
            // закрываем тултип, если открыт другой
            if (event.detail !== this.id) {
                this.open = false;
            }
        }
    }"
                                x-init="
        window.addEventListener('resize', () => update());
        window.addEventListener('scroll', () => update(), true);
        window.addEventListener('promo-tooltip-open', (event) => handleOpenEvent(event));
    "
                            >
    <button
        type="button"
        x-ref="btn"
        @click.stop="
            open = !open;
            if (open) {
                window.dispatchEvent(new CustomEvent('promo-tooltip-open', { detail: id }));
                update();
            }
        "
        class="flex items-center justify-center w-6 h-6 rounded-full text-[#FF7500] focus:outline-none"
    >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <text x="12" y="16" text-anchor="middle"
                  font-size="13" fill="#fff"
                  font-family="Arial, sans-serif">?</text>
        </svg>
    </button>

    <div
        x-ref="tip"
        x-show="open"
        x-transition
        x-cloak
        @click.outside="open = false"
        :style="style"
        class="fixed z-50
               max-w-[calc(100vw-16px)] w-64
               max-h-[calc(100vh-32px)] overflow-y-auto
               bg-white border border-gray-200 rounded-lg shadow-lg
               text-xs text-gray-700 p-2"
    >
        <div class="flex justify-end mb-1">
            <button
                type="button"
                class="w-5 h-5 inline-flex items-center justify-center text-gray-500 hover:text-gray-700"
                @click.stop="open = false"
                aria-label="{{ st('all.close', 'Закрити') }}"
            >
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <div>{{ $promo['description'] }}</div>
    </div>
</span>

                        @endif
                    </span>
                </label>
            @endforeach
        </div>
    </div>
@endif
@push('scripts')
    <script>
        function availablePromosComponent(initialSelected) {
            return {
                selected: initialSelected || 'none',

                change(value) {
                    this.selected = value;
                    this.apply();
                },

                apply() {
                    fetch('{{ $promoUrl }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ promo: this.selected }),
                    })
                        .then(r => r.json())
                        .then(data => {
                            // 1) если нужно авторизоваться
                            if (data.requires_auth) {
                                // откатываем выбор на "Без акции"
                                this.selected = 'none';
                                const noneRadio = document.querySelector('input[name="promo_radio"][value="none"]');
                                if (noneRadio) {
                                    noneRadio.checked = true;
                                }

                                // простое сообщение (можно заменить на свой toast)
                                // Открываем модальное окно вместо alert
                                window.dispatchEvent(new CustomEvent('show-auth-modal', {
                                    detail: {
                                        message: data.message || 'Увійдіть, щоб застосувати акцію.'
                                    }
                                }));


                                return;
                            }

                            // 2) другие ошибки
                            if (!data.ok) {
                                return;
                            }

                            // 3) успешный пересчёт — как уже было
                            const discountEl = document.querySelector('[data-checkout-discount]');
                            if (discountEl) {
                                discountEl.textContent = Number(data.discount || 0).toLocaleString('uk-UA', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                });
                            }

                            if (window.checkoutTotals && typeof window.checkoutTotals.setPromoDiscount === 'function') {
                                window.checkoutTotals.setPromoDiscount(0);
                            }

                            const totalUahEl = document.querySelector('[data-checkout-total-uah]');
                            const totalKopEl = document.querySelector('[data-checkout-total-kop]');

                            if (totalUahEl) {
                                totalUahEl.textContent = data.total_uah_formatted ?? data.total_uah;
                            }
                            if (totalKopEl) {
                                totalKopEl.textContent = data.total_kop;
                            }
                        })
                        .catch(() => {});
                },
            }
        }

    </script>
@endpush
