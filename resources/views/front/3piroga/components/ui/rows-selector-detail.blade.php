@props([
'rows'            => [],
'characteristics' => [],          // [{id, slug, title, svg, sort}]
'rootId'          => null,        // дефолтно rows[0]
'defaultPrice'    => 0,
'defaultOldPrice' => null,
'cartText'        => 'Додати в кошик',
'personSlug'      => 'persons',

// NEW:
'store'           => 'sku',       // имя Alpine store
'initStore'       => false,       // создавать ли store тут
])

@php
    // базовая подготовка (для инициализации стора и кнопок)
    $rootId  = $rootId ?? ($rows[0]['product_id'] ?? null);
    $rootKey = $rootId !== null ? (string)$rootId : '';

    // карта цен (нужна родителю/стору)
    $priceMap = [];
    foreach ($rows as $row) {
        $priceMap[(string)$row['product_id']] = [
            'price' => (float)($row['price'] ?? 0),
            'old'   => isset($row['old_price']) ? (float)$row['old_price'] : null,
        ];
    }

    // раскладка характеристик: 1–2 слева, 3-я справа
    $charsSorted = collect($characteristics)->sortBy('sort')->values();
    $leftChars   = $charsSorted->slice(0, 2);
    $rightChar   = $charsSorted->get(2);
@endphp

@if(!empty($rows))
    <div
        @if($initStore)
        x-data
        x-init="
            // создаём/обновляем общий стор для страницы товара
            const name = '{{ $store }}';
            const init = {
                selected: '{{ $rootKey }}',
                prices: @js($priceMap),
                fmt(v){ const n=Number(v||0); const parts=n.toFixed(2).split('.'); return {uah: parts[0].replace(/\B(?=(\d{3})+(?!\d))/g,' '), kop: parts[1]}; },
                price(){ const p=this.prices[this.selected]; return p?.price ?? {{ (float)$defaultPrice }}; },
                old(){ const p=this.prices[this.selected]; return (p?.old && p.old > (p?.price ?? 0)) ? p.old : null; },
            };
            if (!Alpine.store(name)) {
                Alpine.store(name, init);
            } else {
                // мягкое обновление, если стор уже есть
                const s = Alpine.store(name);
                s.prices   = s.prices ?? init.prices;
                s.selected = s.selected ?? init.selected;
                s.fmt      = s.fmt ?? init.fmt;
                s.price    = s.price ?? init.price;
                s.old      = s.old ?? init.old;
            }
        "
        @endif
        class="md:mt-4"
    >
        {{-- КНОПКИ-«ПИЛЮЛИ»: на мобиле 1 кол., на md+ — 2 колонки, одинаковой ширины --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 min-w-[292px] text-xs">
            @foreach($rows as $r)
                @php $id = (string)($r['product_id']); @endphp
                <button
                    type="button"
                    x-on:click="$store['{{ $store }}'].selected='{{ $id }}'"
                    class="group w-full inline-flex items-center justify-between gap-2 rounded-[8px] px-2 py-2 border transition
                       md:h-[40px]
                       data-[active=true]:bg-[#FF7500] data-[active=true]:text-white data-[active=true]:border-[#FF7500]
                       data-[active=false]:bg-white data-[active=false]:text-[#333] data-[active=false]:border-[#E6E6E6] hover:border-[#FF7500]"
                    :data-active="$store['{{ $store }}'].selected === '{{ $id }}'"
                >
                    {{-- слева 1–2 характеристики (иконка + текст) --}}
                    <span class="flex items-center gap-3">
                    @foreach($leftChars as $char)
                            @php
                                $val = $r['char_values'][$char['id']] ?? null;
                                $svg = $char['svg'] ?? null;
                            @endphp
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            @if($svg)
                                    <span aria-hidden="true" class="inline-block h-4 w-4"
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

                    {{-- справа 3-я характеристика (обычно «персоны») --}}
                    @if($rightChar)
                        @php
                            $valRaw    = $r['char_values'][$rightChar['id']] ?? null;
                            $val       = is_array($valRaw) ? (string)($valRaw['title'] ?? reset($valRaw)) : (string)($valRaw ?? '');
                            $isPersons = ($rightChar['slug'] ?? null) === $personSlug;
                            $people    = 1;
                            if ($isPersons) {
                                $digits = (int) preg_replace('/\D+/', '', (string)$val);
                                $people = max(1, $digits ?: (is_numeric($val) ? (int)$val : 1));
                            }
                            $svgRight = $rightChar['svg'] ?? null;
                            $personIcon = $svgRight
                                ? '<span aria-hidden="true" class="inline-block h-4 w-[8px]"
                                    style="background-color: currentColor;
                                    mask-image:url(\'' . e($svgRight) . '\'); -webkit-mask-image:url(\'' . e($svgRight) . '\');
                                    mask-repeat:no-repeat; -webkit-mask-repeat:no-repeat;
                                    mask-position:center; -webkit-mask-position:center;
                                    mask-size:contain; -webkit-mask-size:contain;"></span>'
                                : '';
                        @endphp
                        <span class="inline-flex items-center whitespace-nowrap">
                        @if($isPersons)
                                @if($people <= 4)
                                    {!! str_repeat($personIcon, $people) !!}
                                @else
                                    {!! $personIcon !!}
                                @endif
                                {{-- На странице товара показываем текст для всех вариантов --}}
                                @if(!empty(trim($val)))<span class="ml-1">{{ $val }}</span>@endif
                            @else
                                {!! $personIcon !!}
                                @if(!empty(trim($val)))<span class="ml-1">{{ $val }}</span>@endif
                            @endif
                    </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
@endif
