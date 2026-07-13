@php
    $locale = app()->getLocale();
    $cartUrl = in_array($locale, ['ru', 'en'], true)
        ? (Route::has('localized.cart.page') ? route('localized.cart.page', ['locale' => $locale]) : url('/' . $locale . '/cart'))
        : (Route::has('cart.page') ? route('cart.page') : url('/cart'));
@endphp

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('checkoutOrderItems', () => {
        const addUrl = @json($addUrl);
        const removeUrl = @json($removeUrl);
        const csrfToken = @json(csrf_token());
        const currencyShort = @json(st('cart.summary.currency_short', 'грн.'));

        async function sendRequest(url, payload) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload)
                });
                return await res.json();
            } catch (e) {
                console.error('cart request error:', e);
                return {};
            }
        }

        function updateDom(data, productId) {
            try {
                const productIdStr = String(productId || '').split('"').join('&quot;');
                const row = document.querySelector('[data-cart-item="' + productIdStr + '"]');
                if (!row) return;

                if (data && data.item) {
                    const it = data.item;
                    if (data.removed || Number(it.qty) <= 0) {
                        row.remove();
                    } else {
                      //  const qtyEl = row.querySelector('[data-cart-line-total]')?.parentElement?.parentElement?.querySelector('.font-semibold');
                        //if (qtyEl) qtyEl.textContent = String(it.qty ?? 0);
                        const qtyEl = row.querySelector('[data-cart-qty]');
                        if (qtyEl) qtyEl.textContent = `${it.qty ?? 0} шт`;
                        const lineTotal = row.querySelector('[data-cart-line-total]');
                        if (lineTotal) {
                            const price = Number(it.line_total || it.subtotal || 0);
                            const uah = Math.floor(price);
                            const kop = Math.round((price - uah) * 100);
                            lineTotal.textContent = new Intl.NumberFormat('uk-UA').format(uah);
                            const sup = lineTotal.nextElementSibling;
                            if (sup && sup.tagName === 'SUP') {
                                sup.textContent = String(kop).padStart(2, '0');
                            }
                        }
                    }
                } else if (data?.removed) {
                    row.remove();
                }

                // Обновляем итоги
                const qty = Number(data?.qty ?? data?.total_qty ?? 0);
                const total = Number(data?.total_price ?? data?.total ?? 0);

                if (window.Alpine && Alpine.store('cart')) {
                    Alpine.store('cart').setQty(qty);
                    Alpine.store('cart').setTotal(total);
                }

                const fmt = (v) =>
                    new Intl.NumberFormat('uk-UA').format(Number(v || 0)) + ' ' + currencyShort;

                const totalEl = document.querySelector('[data-cart-total]');
                if (totalEl) totalEl.textContent = fmt(total);

                const subEl = document.querySelector('[data-checkout-subtotal]');
                if (subEl) subEl.textContent = fmt(total);

                const grandEl = document.querySelector('[data-checkout-total]');
                if (grandEl) grandEl.textContent = fmt(total);

                document.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
            } catch (e) {
                console.error('updateDom error:', e);
            }
        }

        return {
            async inc(payload) {
                const id = (typeof payload === 'object') ? payload.id : payload;
                const price = (typeof payload === 'object') ? payload.price : null;
                const data = await sendRequest(addUrl, {
                    product_id: id,
                    qty: 1,
                    price: price
                });
                updateDom(data, id);
                return data;
            },
            async dec(payload) {
                const id = (typeof payload === 'object') ? payload.id : payload;
                const price = (typeof payload === 'object') ? payload.price : null;
                const data = await sendRequest(addUrl, {
                    product_id: id,
                    qty: -1,
                    price: price
                });
                updateDom(data, id);
                return data;
            },
            async doRemove(payload) {
                const id = (typeof payload === 'object') ? payload.id : payload;
                const data = await sendRequest(removeUrl, { product_id: id });
                updateDom(data, id);
                return data;
            }
        };
    });
});
</script>
@endpush

<div
    class="bg-white rounded-[12px] shadow-[0_2px_10px_rgba(0,0,0,.08)] p-4 space-y-4"
    x-data="checkoutOrderItems"
    @cart-remove.stop="doRemove($event.detail)"
>
    <div class="flex items-start justify-between gap-2">
        <div class="checkout-section-title">{{ st('cart.miy-zakaz', 'Мій заказ') }}</div>

        <a href="{{ $cartUrl }}"
           class="text-[#FF7500] font-medium hover:underline text-sm md:text-base whitespace-nowrap">{{ st('cart.redaguvaty', 'Редагувати') }}</a>
    </div>

    <div class="space-y-4">
        @foreach($items as $it)
            @php
                $pid  = $it['product_id'];
                $qty  = (int)($it['qty'] ?? 1);
                $name = $it['name'] ?? st('cart.item.default_name', 'Товар');
                $img  = $it['image'] ?? asset('images/placeholder-4x3.jpg');
                $var   = data_get($it, 'variant');
                $price = (float)($it['subtotal'] ?? 0);
                $uah   = floor($price);
                $kop   = sprintf('%02d', (int)round(($price - $uah) * 100));

                // Получаем характеристики с SVG иконками для размера и веса, а также старую цену и ссылку на товар
                $variantChars = [];
                $old = null;
                $productUrl = null;
                if ($pid) {
                    $product = \App\Models\Shop\Product::with([
                        'productCharacteristicValues.characteristic:id,slug,svg_image_id',
                        'productCharacteristicValues.characteristic.svgImage',
                        'productCharacteristicValues.characteristicValue',
                        'parent',
                        'parent.mainCategory:id,slug',
                        'mainCategory:id,slug',
                    ])->find($pid);
                    if ($product) {
                        // Получаем старую цену из продукта или родителя
                        $parent = $product->parent ?? $product;
                        $oldPrice = $parent->old_price ?? $product->old_price ?? null;
                        if ($oldPrice && $oldPrice > 0) {
                            $currentUnitPrice = (float)($it['price'] ?? 0);
                            // Если есть старая цена и она больше текущей, считаем old_subtotal
                            if ($oldPrice > $currentUnitPrice) {
                                $old = $oldPrice * $qty;
                            }
                        }

                        // Ссылка на страницу товара (категория — у продукта или у родителя)
                        $cat = $product->mainCategory ?? $product->parent?->mainCategory;
                        if ($cat && $product->slug) {
                            $productUrl = route('product.show', ['categorySlug' => $cat->slug, 'itemSlug' => $product->slug]);
                        }

                        // Получаем характеристики
                        if ($product->relationLoaded('productCharacteristicValues')) {
                            $vals = $product->productCharacteristicValues;
                            $keep = ['rozmir-pirogiv', 'vaga']; // размер и вес
                            foreach ($vals as $v) {
                                $char = $v->characteristic;
                                if (!$char) continue;

                                $slug = (string) ($char->slug ?? '');
                                if ($slug === '' || !in_array($slug, $keep, true)) {
                                    continue;
                                }
                                $text = $v->value_text ?: ($v->characteristicValue?->value ?? null);
                                if ($text) {
                                    $svgUrl = $char->svgImage?->url ?? null;
                                    $variantChars[] = [
                                        'slug' => $slug,
                                        'value' => $text,
                                        'svg' => $svgUrl,
                                    ];
                                }
                            }
                        }
                    }
                }

                // Если old_subtotal уже есть в данных, используем его
                if (!isset($old) && isset($it['old_subtotal']) && $it['old_subtotal'] > 0) {
                    $old = (float)$it['old_subtotal'];
                }
            @endphp

            <div class="flex flex-col md:grid md:grid-cols-[290px_90px_1fr] md:items-center gap-3 md:gap-4 border border-[#F1F2F4] rounded-[12px]
            shadow-[0_2px_8px_rgba(0,0,0,.06)] p-3 md:p-2 md:min-h-[116px]"
                 data-cart-item="{{ $pid }}">



            {{-- ПЕРВЫЙ РЯД: изображение + описание и размеры (мобильная версия) --}}
                <div class="md:hidden flex items-start gap-3 w-full">
                    {{-- изображение (клик — переход на товар) --}}
                    @if($productUrl)
                        <a href="{{ $productUrl }}" class="shrink-0 rounded-[8px] overflow-hidden hover:opacity-90 transition-opacity" aria-label="{{ $name }}">
                            <img src="{{ $img }}" alt="" class="w-[120px] h-[96px] rounded-[8px] object-cover">
                        </a>
                    @else
                        <img src="{{ $img }}" alt="" class="w-[120px] h-[96px] rounded-[8px] object-cover shrink-0">
                    @endif

                    {{-- описание и размеры --}}
                    <div class="flex-1 min-w-0">
                        @if($productUrl)
                            <a href="{{ $productUrl }}" class="text-[10px] font-semibold text-[#272828] line-clamp-2 hover:text-[#FF7500] transition-colors block">{{ $name }}</a>
                        @else
                            <div class="text-[10px] font-semibold text-[#272828] line-clamp-2">{{ $name }}</div>
                        @endif

                        @if(!empty($variantChars))
                            <div class="mt-1 flex flex-row items-center gap-2 text-[12px] text-[#9CA3AF]">
                                @foreach($variantChars as $char)
                                    <span class="inline-flex items-center gap-1">
                                        @if($char['svg'])
                                            <span aria-hidden="true" class="inline-block h-4 w-4 shrink-0"
                                                  style="background-color: currentColor;
                                                      mask-image:url('{{ $char['svg'] }}');-webkit-mask-image:url('{{ $char['svg'] }}');
                                                      mask-repeat:no-repeat;-webkit-mask-repeat:no-repeat;
                                                      mask-position:center;-webkit-mask-position:center;
                                                      mask-size:contain;-webkit-mask-size:contain;"></span>
                                        @endif
                                        <span>{{ $char['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @elseif(!empty($it['meta_line']))
                        <div class="mt-1 text-[12px] text-[#9CA3AF]">{!! $it['meta_line'] !!}</div>
                    @elseif($var)
                        <div class="mt-1 text-[12px] text-[#9CA3AF]">{{ $var }}</div>
                    @endif


                    </div>
                </div>

                <div class="hidden md:flex items-start gap-3 w-[290px] min-w-[290px]">
                    @if($productUrl)
                        <a href="{{ $productUrl }}" class="shrink-0 rounded-[8px] overflow-hidden hover:opacity-90 transition-opacity" aria-label="{{ $name }}">
                            <img src="{{ $img }}" alt="" class="w-[120px] h-[96px] rounded-[8px] object-cover">
                        </a>
                    @else
                        <img src="{{ $img }}" alt="" class="w-[120px] h-[96px] rounded-[8px] object-cover shrink-0">
                    @endif

                    <div class="flex-1 min-w-0">
                        @if($productUrl)
                            <a href="{{ $productUrl }}" class="text-[10px] font-semibold text-[#272828] line-clamp-2 hover:text-[#FF7500] transition-colors block">{{ $name }}</a>
                        @else
                            <div class="text-[10px] font-semibold text-[#272828] line-clamp-2">{{ $name }}</div>
                        @endif

                        @if(!empty($variantChars))
                            <div class="mt-1 flex flex-row items-center gap-2 text-[12px] text-[#9CA3AF]">
                                @foreach($variantChars as $char)
                                    <span class="inline-flex items-center gap-1">
                        @if($char['svg'])
                                            <span aria-hidden="true" class="inline-block h-4 w-4 shrink-0"
                                                  style="background-color: currentColor;
                                                      mask-image:url('{{ $char['svg'] }}');-webkit-mask-image:url('{{ $char['svg'] }}');
                                                      mask-repeat:no-repeat;-webkit-mask-repeat:no-repeat;
                                                      mask-position:center;-webkit-mask-position:center;
                                                      mask-size:contain;-webkit-mask-size:contain;"></span>
                                        @endif
                        <span>{{ $char['value'] }}</span>
                    </span>
                                @endforeach
                            </div>
                        @elseif(!empty($it['meta_line']))
                            <div class="mt-1 text-[12px] text-[#9CA3AF]">{!! $it['meta_line'] !!}</div>
                        @elseif($var)
                            <div class="mt-1 text-[12px] text-[#9CA3AF]">{{ $var }}</div>
                        @endif


                    </div>
                </div>


                {{-- ВТОРОЙ РЯД (моб): qty + цена + удалить --}}
                <div class="md:hidden flex items-center justify-between gap-3 w-full">
                    {{-- слева: количество --}}
                    <div
                        class="w-[90px] h-[37px] shrink-0 flex items-center justify-center
           text-[#FF7500] text-[18px] leading-none font-bold"
                        data-cart-qty
                    >
                        {{ $qty }} шт
                    </div>


                    {{-- середина: цена --}}
                    <div class="flex-1 min-w-0">
                        <div class="text-left whitespace-nowrap">
                            <div class="flex items-baseline gap-1 text-[#E44800] font-bold leading-none">
                <span class="text-[18px] tabular-nums" data-cart-line-total>
                    {{ number_format($uah, 0, ',', ' ') }}
                </span>
                                <sup class="align-top text-[12px] font-semibold tabular-nums">{{ $kop }}</sup>
                                <span class="text-[14px]">{{ st('cart.summary.currency_short', 'грн.') }}</span>
                            </div>

                            @if($old && $old > $price)
                                <div class="text-[16px] text-[#9E9E9E] line-through tabular-nums">
                                    {{ number_format($old, 0, ',', ' ') }} {{ st('cart.summary.currency_short', 'грн') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- справа: удалить
                    <div x-data="{ ask:false }" class="relative shrink-0">
                        <button
                            type="button"
                            class="w-8 h-8 grid place-items-center border border-[#E5E7EB] rounded-[4px] text-[#9CA3AF] hover:text-[#6B7280]"
                            title="Видалити"
                            aria-label="Видалити"
                            @click="ask = true"
                        >
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M0.292893 0.292893C0.683417 -0.0976309 1.31658 -0.0976309 1.70711 0.292893L7 5.58579L12.2929 0.292893C12.6834 -0.0976311 13.3166 -0.0976311 13.7071 0.292893C14.0976 0.683417 14.0976 1.31658 13.7071 1.70711L8.41421 7L13.7071 12.2929C14.0976 12.6834 14.0976 13.3166 13.7071 13.7071C13.3166 14.0976 12.6834 14.0976 12.2929 13.7071L7 8.41421L1.70711 13.7071C1.31658 14.0976 0.683418 14.0976 0.292893 13.7071C-0.0976309 13.3166 -0.0976309 12.6834 0.292893 12.2929L5.58579 7L0.292893 1.70711C-0.0976311 1.31658 -0.0976311 0.683418 0.292893 0.292893Z"
                                      fill="#929292"/>
                            </svg>
                        </button>

                        <div
                            x-show="ask"
                            x-transition
                            x-cloak
                            @click.outside="ask = false"
                            class="absolute right-0 top-full mt-2 bg-white shadow-lg rounded-md border border-gray-200 p-3 w-[180px] z-[100]"
                        >
                            <div class="text-sm text-gray-800 mb-2 text-center">
                                {{ st('cart.vydalyty-tsei-tovar-iz-zamovlennya', 'Видалити цей товар із замовлення') }}?
                            </div>
                            <div class="flex justify-center gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded-md text-white bg-[#FF7500] hover:bg-[#e56700] text-sm font-semibold"
                                    @click="$dispatch('cart-remove', { id: {{ $pid }} }); ask = false"
                                >
                                    {{ st('cart.yes', 'Так') }}
                                </button>
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded-md text-gray-600 bg-gray-100 hover:bg-gray-200 text-sm font-medium"
                                    @click="ask = false"
                                >
                                    {{ st('cart.no', 'Ні') }}
                                </button>
                            </div>
                        </div>
                    </div>--}}
                </div>


                {{-- КОЛОНКА 3: кнопки - и + (неактивные, десктоп) --}}
                <div class="hidden md:flex items-center justify-self-center">
                    <div
                        class="w-[90px] h-[37px] flex items-center justify-center
               text-[#FF7500] text-[18px] leading-none font-bold"
                        data-cart-qty
                    >
                        {{ $qty }} шт
                    </div>
                </div>


                {{-- КОЛОНКА 4: цена, старая цена и кнопка удалить (десктоп) --}}
                <div class="hidden md:flex items-start gap-3 justify-self-end">

                {{-- цена --}}
                    <div class="text-right whitespace-nowrap">
                        <div class="flex items-baseline justify-end gap-1 text-[#E44800] font-bold leading-none">
                            <span class="text-[18px] tabular-nums" data-cart-line-total>
                                {{ number_format($uah, 0, ',', ' ') }}
                            </span>
                            <sup class="align-top text-[12px] font-semibold tabular-nums">{{ $kop }}</sup>
                            <span class="text-[14px]">{{ st('cart.summary.currency_short', 'грн.') }}</span>
                        </div>

                        @if($old && $old > $price)
                            <div class="text-[16px] text-[#9E9E9E] line-through tabular-nums">
                                {{ number_format($old, 0, ',', ' ') }} {{ st('cart.summary.currency_short', 'грн') }}
                            </div>
                        @endif
                    </div>

                    {{-- удалить с кастомным confirm
                    <div x-data="{ ask:false }" class="relative shrink-0">
                        <button
                            type="button"
                            class="w-8 h-8 grid place-items-center border border-[#E5E7EB] rounded-[4px] text-[#9CA3AF] hover:text-[#6B7280]"
                            title="Видалити"
                            aria-label="Видалити"
                            @click="ask = true"
                        >
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M0.292893 0.292893C0.683417 -0.0976309 1.31658 -0.0976309 1.70711 0.292893L7 5.58579L12.2929 0.292893C12.6834 -0.0976311 13.3166 -0.0976311 13.7071 0.292893C14.0976 0.683417 14.0976 1.31658 13.7071 1.70711L8.41421 7L13.7071 12.2929C14.0976 12.6834 14.0976 13.3166 13.7071 13.7071C13.3166 14.0976 12.6834 14.0976 12.2929 13.7071L7 8.41421L1.70711 13.7071C1.31658 14.0976 0.683418 14.0976 0.292893 13.7071C-0.0976309 13.3166 -0.0976309 12.6834 0.292893 12.2929L5.58579 7L0.292893 1.70711C-0.0976311 1.31658 -0.0976311 0.683418 0.292893 0.292893Z"
                                      fill="#929292"/>
                            </svg>
                        </button>

                        <div
                            x-show="ask"
                            x-transition
                            @click.outside="ask = false"
                            class="absolute right-0 top-full mt-2 bg-white shadow-lg rounded-md border border-gray-200 p-3 w-[180px] z-20"
                        >
                            <div class="text-sm text-gray-800 mb-2 text-center">
                                {{ st('cart.vydalyty-tsei-tovar-iz-zamovlennya', 'Видалити цей товар із замовлення') }}?
                            </div>
                            <div class="flex justify-center gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded-md text-white bg-[#FF7500] hover:bg-[#e56700] text-sm font-semibold"
                                    @click="$dispatch('cart-remove', { id: {{ $pid }} }); ask = false"
                                >
                                    {{ st('cart.yes', 'Так') }}
                                </button>
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded-md text-gray-600 bg-gray-100 hover:bg-gray-200 text-sm font-medium"
                                    @click="ask = false"
                                >
                                    {{ st('cart.no', 'Ні') }}
                                </button>
                            </div>
                        </div>
                    </div>--}}
                </div>
            </div>
        @endforeach
    </div>
</div>
