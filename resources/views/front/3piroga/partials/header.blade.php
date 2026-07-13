<header id="site-header" class="sticky top-0 z-[60] w-full bg-white">
    <div class="mx-auto w-full desk:w-[1343px] px-4 md:px-6">
    <div
        x-data="{}"
        @keydown.escape.window="$store.search.open = false"
        x-effect="document.body.classList.toggle('overflow-hidden', $store.search.open && window.matchMedia('(max-width: 1023px)').matches)"
        class="border-b border-black/10 pb-2 w-full desk:h-[76px] md:h-16"
    >
        <div class="flex items-center justify-between min-h-[68px]" @click.outside="$store.search.open = false">
            {{-- ЛЕВЫЙ МИНИ-БЛОК: бургер + логотип + телефон + язык --}}
            <div class="flex items-center md:gap-5 desk:gap-8 gap-4">
                {{-- burger --}}
                <button type="button"
                        x-data
                        @click="$dispatch('open-mobile-menu')"
                        class="inline-flex items-center justify-center w-6 h-10 rounded-lg hover:bg-gray-100"
                        aria-label="Меню">
                    <img src="{{ asset('images/menu.svg') }}" class="w-6 h-6" alt="">
                </button>

                {{-- logo --}}
                <a href="{{ in_array($locale ?? app()->getLocale(), ['ru', 'en'], true) ? route('localized.home', ['locale' => ($locale ?? app()->getLocale())]) : route('home') }}" class="-ml-[4px] block md:ml-0 md:gap-2 desk:gap-6" aria-label="Три Пироги — на главную">
                    <picture>
                        <source media="(min-width: 1250px)" srcset="{{ asset('images/logo.svg') }}">
                        <source media="(min-width: 768px)" srcset="{{ asset('images/logo_m.svg') }}">
                        <img src="{{ asset('images/logo_m.svg') }}" alt="Три Пироги" decoding="async"
                             class="shrink-0 flex-none basis-[52px] md:basis-[57px] max-w-none object-contain" fetchpriority="high">
                    </picture>
                </a>

                {{-- PHONE DROPDOWN --}}
                @php
                    $activePhone = $headerPhonePrimary['display'] ?? config('phones.default');
                    $phones =  $headerPhones ?? config('phones.list', []);
                    $telHref = fn($p) => 'tel:' . preg_replace('/[^\d+]/', '', $p);
                @endphp
                <details
                    class="relative hidden md:block group"
                    x-data
                    @click.outside="$el.open = false"
                    @keydown.escape.window="$el.open = false"
                >
                    <summary
                        class="inline-flex items-center gap-2.5 h-10 px-3 rounded-lg ring-1 ring-black/10 hover:bg-gray-50 cursor-pointer select-none
                        [&::-webkit-details-marker]:hidden">
                        <svg class="hidden lg:block w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8c1.9 3.6 4.9 6.5 8.5 8.5l2.8-2.8c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 .9V22c0 .6-.4 1-1 1C10.1 23 1 13.9 1 2c0-.6.4-1 1-1h4.3c.6 0 .9.4.9 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1L6.6 10.8z"/></svg>
                        <a href="{{ $telHref($activePhone) }}"
                           class="text-sm leading-none font-medium text-gray-900 whitespace-nowrap hover:text-orange-600">
                            {{ $activePhone }}
                        </a>
                        <svg width="14" height="8" class="w-4 h-4 text-gray-600 transition-transform group-open:rotate-180" viewBox="0 0 14 8" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.30232 1.5L5.89549 6.09317C6.43793 6.63561 7.32557 6.63561 7.86801 6.09317L12.4612 1.5" stroke="#19191A" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </summary>
                    <div class="absolute left-0 top-[calc(100%+6px)] z-50 w-64 rounded-xl border bg-white shadow-md p-2">
                        <ul class="space-y-1">
                            @foreach($phones as $phone)
                                <li>
                                    <a href="{{ $telHref($phone['display'] ?? $phone['tel'] ?? '') }}" class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg hover:bg-gray-50">
                                        <span class="text-sm">{{ $phone['display'] }}</span>
                                        @if($headerPhonePrimary && ($phone['tel'] ?? '') === ($headerPhonePrimary['tel'] ?? ''))
                                            <span class="text-[#FF7500] text-xs">{{ st('header.phone.main','основний') }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </details>

                {{-- LANGUAGE DROPDOWN --}}
                @php
                    $locale = app()->getLocale();
                    $langs = ['uk' => 'UA', 'ru' => 'RU', 'en' => 'EN'];
                    $localePrefix = in_array($locale, ['ru', 'en'], true) ? '/' . $locale : '';
                @endphp
                <details
                    class="relative hidden md:block group"
                    x-data
                    @click.outside="$el.open = false"
                    @keydown.escape.window="$el.open = false"
                >
                    <summary
                        class="inline-flex items-center gap-2 h-10 px-3 rounded-lg ring-1 ring-black/10 hover:bg-gray-50 cursor-pointer select-none
                        [&::-webkit-details-marker]:hidden">
                        <span class="font-medium text-sm">{{ $langs[$locale] ?? 'UA' }}</span>
                        <svg width="14" height="8" class="w-4 h-4 text-gray-600 transition-transform group-open:rotate-180" viewBox="0 0 14 8" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.30232 1.5L5.89549 6.09317C6.43793 6.63561 7.32557 6.63561 7.86801 6.09317L12.4612 1.5" stroke="#19191A" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </summary>
                    <div class="absolute left-0 top-[calc(100%+6px)] z-50 w-16 rounded-xl border bg-white shadow-md p-2">
                        <ul class="text-sm">
                            @foreach($langs as $code => $label)
                                <li>
                                    <a href="{{ route('lang.switch', $code) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 @if($locale===$code) text-orange-600 font-medium @endif"
                                       @if($locale===$code) aria-current="true" @endif>
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </details>
            </div>

            {{-- ЦЕНТР: слоган / поиск --}}
            <div class="flex desk:flex-1 justify-between w-full">
                {{-- Слоган (desktop) --}}
                <div
                    x-show="!$store.search.open"
                    x-transition.opacity
                    class="hidden desk:flex items-center ml-[100px] font-normal text-[13px] leading-4 text-[#19191A]"
                >
                    <span class="inline-flex items-center gap-2">
                        <img src="{{ asset('images/fire.svg') }}" class="w-5 h-5 mx-auto" alt="">
                        {{ st('header.wood-fired', "Готуємо в дров'яній печі!") }}
                    </span>
                </div>

                {{-- Поиск как компонент --}}
                @php
                    $locale = app()->getLocale();
                    $searchAction = in_array($locale, ['ru', 'en'], true)
                        ? route('localized.search', ['locale' => $locale])
                        : route('search');
                    $searchSuggest = in_array($locale, ['ru', 'en'], true)
                        ? route('localized.search.suggest', ['locale' => $locale])
                        : route('search.suggest');
                @endphp
                <x-search.header :action="$searchAction" :suggest="$searchSuggest" :placeholder="st('search.placeholder', 'Я шукаю...')" maxWidth="450px" />
            </div>

            {{-- ПРАВО: иконки --}}
            <div class="flex items-center gap-3 md:gap-[20px] shrink-0">
                {{-- Поиск (mobile) --}}
                <button
                    type="button"
                    class="group relative inline-flex items-center justify-center w-5 h-5 shrink-0 lg:hidden text-[#19191A] hover:text-[#FF7500]"
                    aria-label="Пошук"
                    @click.stop="$store.search.open = true"
                >
                    <svg class="w-5 h-5 shrink-0 flex-none" width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M19.5 18.9998L15.4011 14.9009" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10.0556 17.1109C14.2284 17.1109 17.6111 13.7282 17.6111 9.55537C17.6111 5.38255 14.2284 1.99982 10.0556 1.99982C5.88274 1.99982 2.5 5.38255 2.5 9.55537C2.5 13.7282 5.88274 17.1109 10.0556 17.1109Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                {{-- Поиск (desktop) --}}
                <button
                    type="button"
                    class="group relative hidden lg:inline-flex items-center justify-center w-5 h-5 shrink-0 text-[#19191A] hover:text-[#FF7500]"
                    aria-label="Пошук"
                    @click.stop="$store.search.open = !$store.search.open"
                >
                    <svg class="w-5 h-5 shrink-0 flex-none" width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M19.5 18.9998L15.4011 14.9009" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10.0556 17.1109C14.2284 17.1109 17.6111 13.7282 17.6111 9.55537C17.6111 5.38255 14.2284 1.99982 10.0556 1.99982C5.88274 1.99982 2.5 5.38255 2.5 9.55537C2.5 13.7282 5.88274 17.1109 10.0556 17.1109Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                {{-- Акції --}}
                <a
                    href="{{ $localePrefix . '/discounts' }}"
                    class="group inline-flex items-center gap-2 text-sm leading-none font-medium text-[#19191A] hover:text-orange-600 shrink-0"
                >
                    <svg class="w-5 h-5 shrink-0 flex-none text-[#19191A] group-hover:text-[#FF7500]" width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M7 9.76593C8.93437 9.76593 10.5 8.2003 10.5 6.26593C10.5 4.33156 8.93437 2.76593 7 2.76593C5.06562 2.76593 3.5 4.33156 3.5 6.26593C3.5 8.2003 5.06562 9.76593 7 9.76593ZM7 4.76593C7.82812 4.76593 8.5 5.43781 8.5 6.26593C8.5 7.09406 7.82812 7.76593 7 7.76593C6.17188 7.76593 5.5 7.09406 5.5 6.26593C5.5 5.43781 6.17188 4.76593 7 4.76593ZM14 11.7659C12.0656 11.7659 10.5 13.3316 10.5 15.2659C10.5 17.2003 12.0656 18.7659 14 18.7659C15.9344 18.7659 17.5 17.2003 17.5 15.2659C17.5 13.3316 15.9344 11.7659 14 11.7659ZM14 16.7659C13.1719 16.7659 12.5 16.0941 12.5 15.2659C12.5 14.4378 13.1719 13.7659 14 13.7659C14.8281 13.7659 15.5 14.4378 15.5 15.2659C15.5 16.0941 14.8281 16.7659 14 16.7659ZM15.7594 2.77218L16.7469 2.76906C17.3531 2.76593 17.7125 3.45031 17.3625 3.95031L5.91875 18.4409C5.84975 18.5393 5.75807 18.6197 5.65145 18.6752C5.54484 18.7306 5.42644 18.7596 5.30625 18.7597L4.2625 18.7628C3.65312 18.7628 3.29688 18.0784 3.64687 17.5816L15.1469 3.09093C15.2875 2.89093 15.5156 2.77218 15.7594 2.77218Z" fill="currentColor"/>
                    </svg>
                    <span class="hidden lg:inline whitespace-nowrap">{{ st('header.promotions','Акції') }}</span>
                </a>

                {{-- Увійти --}}

                    @include(front_view('partials.header-auth'))


                {{-- Обране --}}
                <a
                    href="{{ in_array($locale, ['ru', 'en'], true) ? route('localized.favorites.index', ['locale' => $locale]) : route('favorites.index') }}"
                    class="group relative inline-flex items-center justify-center w-5 h-5 shrink-0 text-[#19191A] hover:text-[#FF7500]"
                    aria-label="{{ st('menu.favorites', 'Обране') }}"
                >
                    <svg class="w-5 h-5 shrink-0 flex-none" width="20" height="20" viewBox="0 0 21 19" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10.6 16.1409L10.5 16.2409L10.39 16.1409C5.64 11.8309 2.5 8.98094 2.5 6.09094C2.5 4.09094 4 2.59094 6 2.59094C7.54 2.59094 9.04 3.59094 9.57 4.95094H11.43C11.96 3.59094 13.46 2.59094 15 2.59094C17 2.59094 18.5 4.09094 18.5 6.09094C18.5 8.98094 15.36 11.8309 10.6 16.1409ZM15 0.590942C13.26 0.590942 11.59 1.40094 10.5 2.67094C9.41 1.40094 7.74 0.590942 6 0.590942C2.92 0.590942 0.5 3.00094 0.5 6.09094C0.5 9.86094 3.9 12.9509 9.05 17.6209L10.5 18.9409L11.95 17.6209C17.1 12.9509 20.5 9.86094 20.5 6.09094C20.5 3.00094 18.08 0.590942 15 0.590942Z" fill="currentColor"/>
                    </svg>
                    <span
                        x-cloak
                        x-show="$store.favorites && ($store.favorites.qty > 0)"
                        x-text="$store.favorites ? $store.favorites.qty : 0"
                        class="absolute -top-1 right-0 bg-red-600 text-white text-[10px] leading-none rounded-full px-1 min-w-[16px] text-center"
                    >0</span>
                </a>

                {{-- Кошик --}}
                <div class="shrink-0">
                    @include(front_view('partials.header-cart'))
                </div>
            </div>


        </div>
    </div>
    </div>


</header>
{{-- Меню --}}
@include(front_view('partials.menu'))
