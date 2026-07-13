<div
    x-show="filterOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto"
    style="padding-top: 68px;"
    @keydown.escape.window="filterOpen = false"
>
    @php
        $locale = app()->getLocale();

        /** @var \Illuminate\Support\Collection $filterCharacteristicGroups */
        $groups  = $filterCharacteristicGroups ?? collect();
        $meat    = $groups->get('miaso');        // Мʼясо
        $sea     = $groups->get('moreprodukti'); // Морепродукти
        $cheese  = $groups->get('sir');          // Сир
        $sauces  = $groups->get('sousi');        // Соуси
        $veggies = $groups->get('ovoci');        // Овочі

        // Текущее состояние фильтра из запроса
        $request       = request();
        $selectedMenus = collect($request->input('menu', []))->map(fn ($v) => (string) $v)->all();
        $selectedChars = $request->input('chars', []); // ['miaso' => [1,2], 'sir' => [...], ...]
    @endphp

    {{-- фон --}}
    <div class="fixed inset-0 bg-black/40 z-40" @click="filterOpen = false"></div>

    {{-- модальное окно --}}
    <div
        class="relative bg-white rounded-[12px] shadow-xl z-50
               w-full md:w-[90%] lg:w-[1192px]
               max-h-[calc(100vh-100px)] overflow-y-auto
               p-4 md:p-6 my-4 mx-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- оборачиваем всё в форму GET --}}
        @php
            $filterRoute = in_array($locale, ['ru', 'en'], true)
                ? route('localized.catalog.filter', ['locale' => $locale])
                : route('catalog.filter');
        @endphp
        <form method="GET" action="{{ $filterRoute }}">
            {{-- шапка --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/filter.svg') }}" class="w-6 h-6" alt="">
                    <span class="font-bold text-lg text-[#19191A]"> {{ st('all.filter','Фільтр') }}</span>
                </div>

                <button type="button"
                        @click="filterOpen = false"
                        class="w-9 h-9 rounded-lg bg-[#F3F4F6] flex items-center justify-center text-xl"
                        aria-label="{{ st('all.close','Закрити') }}">
                    ✕
                </button>
            </div>

            {{-- ОБЩИЙ контейнер: на LG цена слева 220px, справа колонки --}}
            <div class="mt-4 lg:mt-0 lg:flex lg:items-start lg:gap-8">
                {{-- левая колонка: цена (должна внутри писать price_min / price_max) --}}
                @include(front_view('product.filter-price'))

                {{-- правая часть: 6 колонок категорий --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-x-8 gap-y-8 text-sm">

                    {{-- Меню --}}
                    <div>
                        <div class="flex items-center justify-between border-b border-[#F3F4F6] pb-2 mb-3">
                            <span class="font-semibold text-[#FF7500]">{{ st('all.menu','Меню') }} </span>
                            <span class="text-[#FF7500] text-xs">⌃</span>
                        </div>

                        {{-- пункты меню --}}
                        @foreach($MainMenuItems ?? [] as $item)
                            @php
                                // label (перевод)
                                $raw = $item['label'] ?? '';
                                if (is_string($raw)) {
                                    $data = json_decode($raw, true) ?: [];
                                } elseif (is_array($raw)) {
                                    $data = $raw;
                                } else {
                                    $data = [];
                                }
                                $label =
                                    $data[$locale] ??
                                    $data['uk'] ??
                                    $data['ru'] ??
                                    $data['en'] ??
                                    (string) $raw;

                                // количество товаров
                                $cnt = (int) ($item['count'] ?? 0);

                                $slug = (string) ($item['slug'] ?? '');
                            @endphp

                            @if($cnt > 0 && $slug !== '')
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           class="w-4 h-4 rounded border-[#D1D5DB] text-[#FF7500]"
                                           name="menu[]"
                                           value="{{ $slug }}"
                                           @checked(in_array($slug, $selectedMenus, true))>
                                    <span class="text-[#19191A] text-sm">
                                        {{ $label }}
                                    </span>
                                    <span class="ml-1 text-xs text-[#9CA3AF]">
                                        {{ $cnt }}
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>

                    {{-- Мʼясо --}}
                    @if($meat)
                        <div>
                            <div class="flex items-center justify-between border-b border-[#F3F4F6] pb-2 mb-3">
                                <span class="font-semibold text-[#FF7500]">{{ st('product.myaso','Мʼясо') }}</span>
                                <span class="text-[#FF7500] text-xs">⌃</span>
                            </div>

                            <div class="space-y-1.5">
                                @foreach($meat->values as $val)
                                    @php
                                        $cnt = (int) ($val->products_count ?? 0);
                                        if ($cnt === 0) continue;

                                        $checked = in_array($val->id, $selectedChars['miaso'] ?? [], false);
                                    @endphp

                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               class="w-4 h-4 rounded border-[#D1D5DB] text-[#FF7500]"
                                               name="chars[miaso][]"
                                               value="{{ $val->id }}"
                                               @checked($checked)>
                                        <span class="text-[#19191A] text-sm">{{ $val->value }}</span>

                                        <span class="ml-1 text-xs text-[#9CA3AF]">
                                            {{ $cnt }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Морепродукти --}}
                    @if($sea)
                        <div>
                            <div class="flex items-center justify-between border-b border-[#F3F4F6] pb-2 mb-3">
                                <span class="font-semibold text-[#FF7500]">{{ st('product.moreprodukty','Морепродукти') }}</span>
                                <span class="text-[#FF7500] text-xs">⌃</span>
                            </div>

                            <div class="space-y-1.5">
                                @foreach($sea->values as $val)
                                    @php
                                        $cnt = (int) ($val->products_count ?? 0);
                                        if ($cnt === 0) continue;

                                        $checked = in_array($val->id, $selectedChars['moreprodukti'] ?? [], false);
                                    @endphp

                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               class="w-4 h-4 rounded border-[#D1D5DB] text-[#FF7500]"
                                               name="chars[moreprodukti][]"
                                               value="{{ $val->id }}"
                                               @checked($checked)>
                                        <span class="text-[#19191A] text-sm">{{ $val->value }}</span>

                                        <span class="ml-1 text-xs text-[#9CA3AF]">
                                            {{ $cnt }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Сир --}}
                    @if($cheese)
                        <div>
                            <div class="flex items-center justify-between border-b border-[#F3F4F6] pb-2 mb-3">
                                <span class="font-semibold text-[#FF7500]">{{ st('product.syr','Сир') }}</span>
                                <span class="text-[#FF7500] text-xs">⌃</span>
                            </div>

                            <div class="space-y-1.5">
                                @foreach($cheese->values as $val)
                                    @php
                                        $cnt = (int) ($val->products_count ?? 0);
                                        if ($cnt === 0) continue;

                                        $checked = in_array($val->id, $selectedChars['sir'] ?? [], false);
                                    @endphp

                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               class="w-4 h-4 rounded border-[#D1D5DB] text-[#FF7500]"
                                               name="chars[sir][]"
                                               value="{{ $val->id }}"
                                               @checked($checked)>
                                        <span class="text-[#19191A] text-sm">{{ $val->value }}</span>

                                        <span class="ml-1 text-xs text-[#9CA3AF]">
                                            {{ $cnt }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Соуси --}}
                    @if($sauces)
                        <div>
                            <div class="flex items-center justify-between border-b border-[#F3F4F6] pb-2 mb-3">
                                <span class="font-semibold text-[#FF7500]">{{ st('product.sousi','Соуси') }}</span>
                                <span class="text-[#FF7500] text-xs">⌃</span>
                            </div>

                            <div class="space-y-1.5">
                                @foreach($sauces->values as $val)
                                    @php
                                        $cnt = (int) ($val->products_count ?? 0);
                                        if ($cnt === 0) continue;

                                        $checked = in_array($val->id, $selectedChars['sousi'] ?? [], false);
                                    @endphp

                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               class="w-4 h-4 rounded border-[#D1D5DB] text-[#FF7500]"
                                               name="chars[sousi][]"
                                               value="{{ $val->id }}"
                                               @checked($checked)>
                                        <span class="text-[#19191A] text-sm">{{ $val->value }}</span>

                                        <span class="ml-1 text-xs text-[#9CA3AF]">
                                            {{ $cnt }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Овочі --}}
                    @if($veggies)
                        <div>
                            <div class="flex items-center justify-between border-b border-[#F3F4F6] pb-2 mb-3">
                                <span class="font-semibold text-[#FF7500]">{{ st('product.ovochi','Овочі') }}</span>
                                <span class="text-[#FF7500] text-xs">⌃</span>
                            </div>

                            <div class="space-y-1.5">
                                @foreach($veggies->values as $val)
                                    @php
                                        $cnt = (int) ($val->products_count ?? 0);
                                        if ($cnt === 0) continue;

                                        $checked = in_array($val->id, $selectedChars['ovoci'] ?? [], false);
                                    @endphp

                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               class="w-4 h-4 rounded border-[#D1D5DB] text-[#FF7500]"
                                               name="chars[ovoci][]"
                                               value="{{ $val->id }}"
                                               @checked($checked)>
                                        <span class="text-[#19191A] text-sm">{{ $val->value }}</span>

                                        <span class="ml-1 text-xs text-[#9CA3AF]">
                                            {{ $cnt }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- нижняя панель кнопок --}}
            <div class="mt-8 flex justify-end gap-3">
                {{-- Очистити: просто уходим на текущий URL без query --}}
                <a href="{{ url()->current() }}"
                   class="px-5 h-9 rounded-[8px] border border-[#E5E7EB] bg-white
                          text-sm text-[#4B5563] flex items-center justify-center">
                    {{ st('all.ochystyty','Очистити') }}
                </a>

                <button type="submit"
                        class="px-6 h-9 rounded-[8px] bg-[#FF7500] text-white text-sm font-semibold">
                    {{ st('all.filtruvaty','Фільтрувати') }}
                </button>
            </div>
        </form>
    </div>
</div>
