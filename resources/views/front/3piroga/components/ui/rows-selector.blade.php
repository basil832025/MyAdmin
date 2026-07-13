@props([
'rows'            => [],
'characteristics' => [],          // [{id, slug, title, svg, sort}]
'rootId'          => null,
'defaultPrice'    => 0,
'defaultOldPrice' => null,
'cartText'        => 'Додати в кошик',
'personSlug'      => 'persons',
])

@php
    static $rowsSelectorLabels = null;

    if ($rowsSelectorLabels === null) {
        $rowsSelectorLabels = [
            'currency' => st('all.grn', 'грн'),
        ];
    }

    $fmt = function ($val) {
        [$uah, $kop] = explode(',', number_format((float)$val, 2, ',', ' '));
        return ['uah' => $uah, 'kop' => $kop];
    };

    $rootId   = $rootId ?? ($rows[0]['product_id'] ?? null);
    $selectedId = $rows[0]['product_id'] ?? $rootId;
    $rootKey  = $selectedId !== null ? (string)$selectedId : '';
    $rootRow  = null;
    if ($rootId) foreach ($rows as $r) if (($r['product_id'] ?? null) === $rootId) { $rootRow = $r; break; }
    $rootRow ??= $rows[0] ?? ['price'=>$defaultPrice,'old_price'=>$defaultOldPrice,'product_id'=>null];

    // Для одного варианта уменьшаем отступы
    $rowsCount = count($rows ?? []);
    $isSingleVariant = $rowsCount <= 1;

    $p  = $fmt($rootRow['price'] ?? $defaultPrice);
    $op = ($rootRow['old_price'] ?? null) && ($rootRow['old_price'] > ($rootRow['price'] ?? 0)) ? $fmt($rootRow['old_price']) : null;

    $priceMap = [];
    foreach ($rows as $row) {
        $priceMap[(string)$row['product_id']] = [
            'price' => (float)($row['price'] ?? 0),
            'old'   => isset($row['old_price']) ? (float)$row['old_price'] : null,
        ];
    }

    // ********** ВАЖНЫЙ ПАТЧ: persons ВСЕГДА СПРАВА **********
    $chars = collect($characteristics)->sortBy('sort')->values();

    $personsIdx = $chars->search(fn($c) => ($c['slug'] ?? null) === $personSlug);
    if ($personsIdx !== false) {
        $rightChar = $chars[$personsIdx];
        // слева берём любые другие 1–2 характеристики, исключая persons
        $leftChars = $chars->filter(fn($c, $i) => $i !== $personsIdx)->take(2)->values();
    } else {
        // как раньше: 1–2 слева, 3-я справа (если persons нет в наборе)
        $leftChars = $chars->slice(0, 2)->values();
        $rightChar = $chars->get(2);
    }
    // *********************************************************
@endphp

@if(!empty($rows))
    <div
        x-data="{
        selected: @js($rootKey),
        prices:   @js($priceMap),

        fmt(v){
            const n = Number(v||0);
            const parts = n.toFixed(2).split('.');
            return { uah: parts[0].replace(/\B(?=(\d{3})+(?!\d))/g,' '), kop: parts[1] };
        },

        adding: false,
        cartQty: 0,

        init() {
            this.$watch('selected', (newVal) => {
                const detail = {
                    productId: newVal,
                    price: this.prices[newVal]?.price ?? null,
                    oldPrice: this.prices[newVal]?.old ?? null,
                };

                const parentCard = this.$el.closest('article[x-data]');
                if (parentCard && window.Alpine) {
                    const cardData = window.Alpine.$data(parentCard);
                    if (cardData && cardData.handleVariantSelected) {
                        cardData.handleVariantSelected({ detail });
                    }
                }

                this.$dispatch('variant-selected', detail);

                this.checkCartQty();
            });

            this.$nextTick(() => {
                const detail = {
                    productId: this.selected,
                    price: this.prices[this.selected]?.price ?? null,
                    oldPrice: this.prices[this.selected]?.old ?? null,
                };

                const parentCard = this.$el.closest('article[x-data]');
                if (parentCard && window.Alpine) {
                    const cardData = window.Alpine.$data(parentCard);
                    if (cardData && cardData.handleVariantSelected) {
                        cardData.handleVariantSelected({ detail });
                    }
                }

                this.$dispatch('variant-selected', detail);

                setTimeout(() => {
                    this.checkCartQty();
                }, Math.random() * 50 + 10);
            });

            window.addEventListener('cart-updated', (e) => {
                if (e?.detail?.item?.product_id === parseInt(this.selected)) {
                    this.cartQty = e.detail.item?.qty ?? 0;
                } else if (e?.detail?.items) {
                    const item = e.detail.items.find(i => parseInt(i.product_id) === parseInt(this.selected));
                    if (item) {
                        this.cartQty = item.qty ?? 0;
                    }
                }
            });
        },

        async checkCartQty() {
            try {
                const cache = window.__CART_CACHE__;
                if (!cache) {
                    const res = await fetch('{{ route('cart.info') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    const item = (data?.items ?? []).find(i => parseInt(i.product_id) === parseInt(this.selected));
                    this.cartQty = item?.qty ?? 0;
                    return;
                }

                const data = await cache.get();
                const item = (data?.items ?? []).find(i => parseInt(i.product_id) === parseInt(this.selected));
                this.cartQty = item?.qty ?? 0;
            } catch (e) {
                this.cartQty = 0;
            }
        },

        async addToCart() {
            if (this.adding) return;
            this.adding = true;

            try {
                const data = await window.CartAPI.add('{{ route('cart.add') }}', {
                    product_id: this.selected,
                    qty: 1,
                    price: this.prices[this.selected]?.price ?? null,
                });

                this.cartQty = data?.item?.qty ?? 1;

                this.$dispatch('notify', { text: 'Додано до кошика', type: 'success' });

            } catch (e) {
                console.error(e);
                alert('Не вдалося додати до кошика');
            } finally {
                this.adding = false;
            }
        },

        async incrementQty() {
            if (this.adding) return;
            this.adding = true;

            try {
                const data = await window.CartAPI.add('{{ route('cart.add') }}', {
                    product_id: this.selected,
                    qty: 1,
                    price: this.prices[this.selected]?.price ?? null,
                });

                this.cartQty = data?.item?.qty ?? this.cartQty + 1;

            } catch (e) {
                console.error(e);
                alert('Не вдалося оновити кількість');
            } finally {
                this.adding = false;
            }
        },

        async decrementQty() {
            if (this.adding || this.cartQty <= 0) return;
            this.adding = true;

            try {
                const data = await window.CartAPI.add('{{ route('cart.add') }}', {
                    product_id: this.selected,
                    qty: -1,
                    price: this.prices[this.selected]?.price ?? null,
                });

                this.cartQty = data?.item?.qty ?? Math.max(0, this.cartQty - 1);

            } catch (e) {
                console.error(e);
                alert('Не вдалося оновити кількість');
            } finally {
                this.adding = false;
            }
        },
    }"
        class="{{ $isSingleVariant ? 'mt-1' : 'mt-1' }} text-[13px] flex flex-col"
    >

        <div class="flex flex-col gap-2">
        @foreach ($rows as $r)
            @php
                $rowValue = (string) ($r['product_id'] ?? '');
            @endphp
            <button
                type="button"
                x-on:click="selected = '{{ $rowValue }}'"
                class="desk:w-[354px] md:w-[336px] w-[331px] flex items-center justify-between rounded-lg border px-3 py-2 transition-colors"
                :class="String(selected || '') === '{{ $rowValue }}' ? 'bg-[#FF7500] border-transparent text-white' : 'bg-white border-[#E5E7EB] text-[#666666] hover:border-[#FF7500]/50'"
            >
                <span class="inline-flex items-center gap-2">
                    @foreach($leftChars as $i => $char)
                        @php
                            $val = $r['char_values'][$char['id']] ?? null;
                            $svg = $char['svg'] ?? null;
                        @endphp
                        <span class="inline-flex items-center gap-2 {{ $i ? 'ml-6 md:ml-8' : '' }}">
                        @if($svg)
                                <span aria-hidden="true" class="inline-block h-5 w-5"
                                      style="background-color: currentColor;
                                          mask-image:url('{{ $svg }}');-webkit-mask-image:url('{{ $svg }}');
                                          mask-repeat:no-repeat;-webkit-mask-repeat:no-repeat;
                                          mask-position:center;-webkit-mask-position:center;
                                          mask-size:contain;-webkit-mask-size:contain;"></span>
                            @endif
                            @if($val)<span>{{ $val }}</span>@endif
                        </span>
                    @endforeach
                </span>

                @if($rightChar)
                    @php
                        $valRaw    = $r['char_values'][$rightChar['id']] ?? null;
                        $val       = is_array($valRaw) ? (string)($valRaw['title'] ?? reset($valRaw)) : (string)$valRaw;

                        $isPersons = ($rightChar['slug'] ?? null) === $personSlug;
                        $people    = 1;
                        if ($isPersons) {
                            $digits = (int) preg_replace('/\D+/', '', (string)$val);
                            $people = max(1, $digits ?: (is_numeric($val) ? (int)$val : 1));
                        }

                        $svgRight = $rightChar['svg'] ?? null;
                        $personIcon = $svgRight
                            ? '<span aria-hidden="true" class="inline-block h-5 w-[10px]"
                                style="background-color: currentColor;
                                mask-image:url(\'' . e($svgRight) . '\'); -webkit-mask-image:url(\'' . e($svgRight) . '\');
                                mask-repeat:no-repeat; -webkit-mask-repeat:no-repeat;
                                mask-position:center; -webkit-mask-position:center;
                                mask-size:contain; -webkit-mask-size:contain;"></span>'
                            : '';
                    @endphp

                    <span class="ml-auto inline-flex items-center whitespace-nowrap justify-end text-right shrink-0">
                    @if($isPersons)
                            @if($people <= 4)
                                {!! str_repeat($personIcon, $people) !!}
                            @else
                                {!! $personIcon !!}
                            @endif
                            @if(!empty(trim($val)))<span class="ml-1">{{ $val }}</span>@endif
                        @else
                            {!! $personIcon !!}
                            @if($val)<span class="ml-1">{{ $val }}</span>@endif
                        @endif
                    </span>
                @endif
            </button>
        @endforeach
        </div>

        {{-- подвал цен + кнопка (для карточек-списка можно оставить; для детальной страницы — убрать) --}}
        <div class="{{ $isSingleVariant ? 'mt-2' : 'mt-3' }} flex items-center justify-between">
            <div class="flex items-baseline gap-1 whitespace-nowrap shrink-0">
                <div class="flex items-baseline gap-1 text-neutral-400 line-through whitespace-nowrap"
                     x-show="prices[selected]?.old && prices[selected]?.old > prices[selected]?.price">
                    <span class="font-semibold text-[16px] leading-[16px]" x-text="fmt(prices[selected]?.old).uah">{{ $op['uah'] ?? '' }}</span>
                    <span class="relative -top-2 font-bold text-[11px] leading-[11px]" x-text="fmt(prices[selected]?.old).kop">{{ $op['kop'] ?? '' }}</span>
                    <span class="text-[12px] leading-[12px]">{{ $rowsSelectorLabels['currency'] }}</span>
                </div>

                <div class="flex items-baseline gap-1 text-[#333333] whitespace-nowrap">
                    <span class="font-bold text-[26px] leading-[32px]" x-text="fmt(prices[selected]?.price).uah">{{ $p['uah'] }}</span>
                    <span class="relative -top-3 font-bold text-[12px] leading-[12px]" x-text="fmt(prices[selected]?.price).kop">{{ $p['kop'] }}</span>
                    <span class="text-[14px] leading-[14px]">{{ $rowsSelectorLabels['currency'] }}</span>
                </div>
            </div>

            {{-- Правая зона (кнопка) фиксированной ширины как в Figma --}}
            <div class="w-[153px] md:w-[173px] shrink-0">
                <button
                    type="button"
                    x-show="cartQty === 0"
                    x-cloak
                    class="w-full inline-flex items-center justify-center text-[12px] h-[36px] gap-2 rounded bg-[#FF7500] px-4 font-semibold text-white shadow-[0_4px_12px_rgba(255,117,0,.35)] transition
               hover:bg-[#ff841f] active:bg-[#e66700] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF7500]/50 disabled:opacity-60"
                    x-bind:data-product-id="selected"
                    @click="addToCart"
                    x-bind:disabled="adding"
                >
                    <template x-if="!adding">
                        <x-icons.cart class="h-5 w-5" />
                    </template>
                    <template x-if="adding">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </template>
                    {{ $cartText }}
                </button>

                <div
                    x-show="cartQty > 0"
                    x-cloak
                    class="w-full inline-flex items-center justify-between bg-[#FDDDA7] text-[#FF7500] h-[36px] rounded px-2"
                >
                    <button
                        type="button"
                        class="w-8 h-8 grid place-items-center text-[22px] leading-none rounded disabled:opacity-40"
                        @click="decrementQty"
                        x-bind:disabled="adding || cartQty <= 0"
                        x-bind:aria-label="cartQty === 1 ? 'Видалити з кошика' : 'Зменшити кількість'"
                    >
                        <svg x-show="cartQty > 1" class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12H19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>

                        <svg x-show="cartQty === 1" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM14 11v6M10 11v6M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="square"/>
                        </svg>
                    </button>

                    <div class="flex-1 text-center font-semibold text-[14px]" x-text="cartQty">1</div>

                    <button
                        type="button"
                        class="w-8 h-8 grid place-items-center text-[22px] leading-none rounded disabled:opacity-40"
                        @click="incrementQty"
                        x-bind:disabled="adding"
                        aria-label="Збільшити кількість"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12H19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            <path d="M12 5V19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>
@endif
