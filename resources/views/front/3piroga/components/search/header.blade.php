@php
    $locale = app()->getLocale();

    $tPlaceholder = st('search.placeholder', 'Я шукаю…', $locale);
    $tClose = st('all.close', 'Закрити', $locale);
    $tSuggestNotFoundTpl = st('search.suggest.not_found', 'Нічого не знайдено для ":q"', $locale);
    $tSuggestGoToCategory = st('search.suggest.go_to_category', 'Перейти у категорію', $locale);
    $tSuggestLoading = st('search.suggest.loading', 'Шукаю…', $locale);

    $defaultAction = in_array($locale, ['ru', 'en'], true)
        ? route('localized.search', ['locale' => $locale])
        : route('search');
    $defaultSuggest = in_array($locale, ['ru', 'en'], true)
        ? route('localized.search.suggest', ['locale' => $locale])
        : route('search.suggest');
@endphp

@props([
'action' => $defaultAction,
'suggest' => $defaultSuggest,
'placeholder' => $tPlaceholder,
'maxWidth' => '620px',   // ширина десктоп-поля
])

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('search', {
                    open: false,
                    q: '',
                    results: { products: [], categories: [] },
                    loading: false,
                    notFound: false,
                    _t: null,

                    notFoundTpl: @json($tSuggestNotFoundTpl),

                    notFoundText(){
                        const tpl = (this.notFoundTpl || '');
                        const q = (this.q || '').trim();
                        return tpl.replace(':q', q);
                    },

                    pLen(){ return (this.results?.products?.length || 0) },
                    cLen(){ return (this.results?.categories?.length || 0) },
                    hasResults(){ return this.pLen() + this.cLen() > 0 },

                    fetchSuggest(q){
                        q = (q||'').trim();
                        if (!q){
                            this.results = {products:[], categories:[]};
                            this.notFound = false;
                            return;
                        }
                        this.loading = true;
                        fetch(@json($suggest) + '?q=' + encodeURIComponent(q), { headers:{'Accept':'application/json'} })
                            .then(r => r.ok ? r.json() : Promise.reject())
                            .then(d => {
                                this.results = d || {products:[], categories:[]};
                                this.notFound = !this.hasResults();
                            })
                            .catch(() => {
                                this.results = {products:[], categories:[]};
                                this.notFound = true;
                            })
                            .finally(() => this.loading = false);
                    },
                });

                // Гарантируем, что поиск закрыт при инициализации и при навигации
                const searchStore = Alpine.store('search');
                if (searchStore) {
                    searchStore.open = false;
                    // Закрываем поиск при навигации
                    window.addEventListener('popstate', () => {
                        if (searchStore) searchStore.open = false;
                    });
                    // Закрываем поиск при видимости страницы
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden && searchStore && searchStore.open) {
                            searchStore.open = false;
                        }
                    });
                }
            })
        </script>
    @endpush
@endonce

<div
    x-data
    x-init="$watch('$store.search.q', v => { clearTimeout($store.search._t); $store.search._t = setTimeout(() => $store.search.fetchSuggest(v), 250) })"
    @keydown.escape.window="$store.search.open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', $store.search.open && window.matchMedia('(max-width: 1023px)').matches)"
>
    {{-- DESKTOP: центральное поле --}}

    <div x-show="$store.search.open"
         x-cloak
         x-transition class="hidden lg:flex w-full px-3">
        <div class="relative w-full mx-auto" style="width: {{ $maxWidth }};">
            <form action="{{ $action }}" method="get">
                <div class="flex items-center gap-2 ring-1 ring-black/10 rounded-[4px] h-10 px-3 bg-white">
                    <img src="{{ asset('images/search.svg') }}" class="w-5 h-5" alt="">
                    <input x-ref="dInput" x-model="$store.search.q" type="text" name="q"
                           placeholder="{{ $placeholder }}" autocomplete="off"
                           class="w-full bg-transparent outline-none text-base"
                           x-init="$watch('$store.search.open', v => { if (v) $nextTick(()=> $refs.dInput?.focus()) })">
                    <button
                        type="button"
                        class="p-2 -m-1"
                        aria-label="{{ $tClose }}"
                        @click="clearTimeout($store.search._t); $store.search._t = null; $store.search.q = ''; $store.search.results = { products: [], categories: [] }; $store.search.notFound = false; $store.search.loading = false; $store.search.open = false"
                    >
                        <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor"><path d="M6.3 6.3a1 1 0 011.4 0L10 8.6l2.3-2.3a1 1 0 111.4 1.4L11.4 10l2.3 2.3a1 1 0 01-1.4 1.4L10 11.4l-2.3 2.3a1 1 0 01-1.4-1.4L8.6 10 6.3 7.7a1 1 0 010-1.4z"/></svg>
                    </button>
                </div>
            </form>

            {{-- пусто --}}
            <div x-show="$store.search.open && $store.search.q && $store.search.notFound"
                 x-transition
                 class="absolute left-0 right-0 top-[calc(100%+8px)] z-50 bg-white rounded-xl ring-1 ring-black/10 shadow-md">
                <div class="px-5 py-4 text-sm text-center text-gray-600">
                    <span class="font-medium" x-text="$store.search.notFoundText()"></span>
                </div>
            </div>

            {{-- результаты --}}
            <div x-show="$store.search.open && $store.search.q && $store.search.hasResults()"
                 x-transition
                 class="absolute left-0 right-0 top-[calc(100%+8px)] z-50 bg-white rounded-xl ring-1 ring-black/10 shadow-md max-h-[70vh] overflow-auto">

                <template x-if="$store.search.pLen()">
                    <div class="px-3 py-3">
                        <ul class="divide-y divide-gray-100">
                            <template x-for="p in ($store.search.results?.products || [])" :key="p.id">
                                <li>
                                    <a :href="p.url" class="flex items-center gap-3 px-2 py-3 hover:bg-gray-50 rounded-md">
                                        <img :src="p.image" class="w-10 h-10 rounded object-cover" alt="">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium truncate" x-text="p.title"></div>
                                            <div class="text-xs text-gray-500" x-show="p.categoryTitle" x-text="p.categoryTitle"></div>
                                        </div>
                                        <svg class="ml-auto shrink-0" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_52742_39039)">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M20.414 7.49323C20.6007 7.56132 20.7528 7.70074 20.8368 7.88087C20.9208 8.061 20.9299 8.26711 20.862 8.45391L17.488 17.7509C17.4556 17.8451 17.4048 17.9319 17.3385 18.0062C17.2722 18.0805 17.1918 18.1409 17.1019 18.1838C17.012 18.2267 16.9145 18.2513 16.815 18.2561C16.7156 18.2609 16.6161 18.2459 16.5225 18.2119C16.4289 18.1779 16.3431 18.1256 16.2699 18.058C16.1967 17.9904 16.1378 17.909 16.0964 17.8184C16.0551 17.7278 16.0322 17.6298 16.0291 17.5303C16.026 17.4308 16.0428 17.3316 16.0784 17.2386L18.8346 9.64313L4.16617 16.4953C3.986 16.5796 3.77973 16.5889 3.59272 16.5211C3.40571 16.4533 3.25329 16.314 3.16899 16.1339C3.08468 15.9537 3.0754 15.7474 3.14318 15.5604C3.21097 15.3734 3.35026 15.221 3.53043 15.1367L18.2029 8.28373L10.6088 5.51141C10.5147 5.47894 10.4279 5.42804 10.3537 5.36169C10.2794 5.29533 10.2191 5.21485 10.1763 5.12494C10.1334 5.03504 10.1089 4.9375 10.1042 4.83802C10.0995 4.73855 10.1146 4.63913 10.1487 4.54556C10.1828 4.45199 10.2352 4.36616 10.3029 4.29306C10.3705 4.21996 10.452 4.16106 10.5426 4.1198C10.6333 4.07854 10.7312 4.05575 10.8308 4.05275C10.9303 4.04975 11.0295 4.0666 11.1224 4.10233L20.414 7.49323Z" fill="#FF7500"/>
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_52742_39039">
                                                    <rect width="24" height="24" fill="white"/>
                                                </clipPath>
                                            </defs>
                                        </svg>

                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                <template x-if="$store.search.cLen()">
                    <div class="px-4 pb-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ $tSuggestGoToCategory }}</div>
                        <div class="grid sm:grid-cols-2 gap-2">
                            <template x-for="c in ($store.search.results?.categories || [])" :key="c.slug">
                                <a :href="c.url" class="flex items-center gap-2 px-3 py-2 rounded ring-1 ring-black/10 hover:bg-gray-50">
                                    <span class="inline-flex w-5 h-5 items-center justify-center rounded ring-1 ring-current/40">▦</span>
                                    <span class="text-sm" x-text="c.title"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- MOBILE/TABLET OVERLAY --}}
    <div x-show="$store.search.open"
         x-cloak
         x-transition.opacity class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" @click="$store.search.open=false"></div>

        <div class="relative mx-auto w-full max-w-[736px] px-4 pt-4" @click.stop>
            <form action="{{ $action }}" method="get"
                  class="h-11 rounded-[4px] ring-1 ring-black/10 bg-white flex items-center gap-2 px-3 shadow-sm">
                <img src="{{ asset('images/search.svg') }}" class="w-5 h-5" alt="">
                <input x-ref="mInput" x-model="$store.search.q" type="text" name="q" autocomplete="off"
                       placeholder="{{ $placeholder }}"
                       class="w-full bg-transparent outline-none text-base"
                       x-init="$watch('$store.search.open', v => { if (v) $nextTick(()=> $refs.mInput?.focus()) })">
                <button
                    type="button"
                    class="p-2 -m-1"
                    aria-label="{{ $tClose }}"
                    @click="clearTimeout($store.search._t); $store.search._t = null; $store.search.q = ''; $store.search.results = { products: [], categories: [] }; $store.search.notFound = false; $store.search.loading = false; $store.search.open = false"
                >
                    <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor"><path d="M6.3 6.3a1 1 0 011.4 0L10 8.6l2.3-2.3a1 1 0 111.4 1.4L11.4 10l2.3 2.3a1 1 0 01-1.4 1.4L10 11.4l-2.3 2.3a1 1 0 01-1.4-1.4L8.6 10 6.3 7.7a1 1 0 010-1.4z"/></svg>
                </button>
            </form>

            <div x-show="$store.search.q" x-transition class="relative">
                <div class="absolute left-1/2 -translate-x-1/2 mt-3
                            w-[calc(100vw-2rem)] sm:w-[520px] md:w-[640px] max-w-[736px]
                            bg-white rounded-xl ring-1 ring-black/10 shadow-md max-h-[70vh] overflow-auto">
                    <template x-if="$store.search.loading">
                        <div class="px-5 py-6 text-sm text-gray-600">{{ $tSuggestLoading }}</div>
                    </template>

                    <template x-if="$store.search.pLen()">
                        <div class="px-3 py-3">
                            <ul class="divide-y divide-gray-100">
                                <template x-for="p in ($store.search.results?.products || [])" :key="p.id">
                                    <li>
                                        <a :href="p.url" class="flex items-center gap-3 px-2 py-3 hover:bg-gray-50 rounded-md">
                                            <img :src="p.image" class="w-10 h-10 rounded object-cover" alt="">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium truncate" x-text="p.title"></div>
                                                <div class="text-xs text-gray-500" x-show="p.categoryTitle" x-text="p.categoryTitle"></div>
                                            </div>
                                            <svg class="ml-auto w-5 h-5 text-[#FF7500]" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/>
                                            </svg>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <template x-if="$store.search.cLen()">
                        <div class="px-4 pb-4">
                            <div class="text-xs font-semibold text-gray-500 mb-2">{{ $tSuggestGoToCategory }}</div>
                            <div class="grid sm:grid-cols-2 gap-2">
                                <template x-for="c in ($store.search.results?.categories || [])" :key="c.slug">
                                    <a :href="c.url" class="flex items-center gap-2 px-3 py-2 rounded ring-1 ring-black/10 hover:bg-gray-50">
                                        <span class="inline-flex w-5 h-5 items-center justify-center rounded ring-1 ring-current/40">▦</span>
                                        <span class="text-sm" x-text="c.title"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="$store.search.notFound && !$store.search.loading">
                        <div class="px-5 py-6 text-center text-sm text-gray-600">
                            <span class="font-medium" x-text="$store.search.notFoundText()"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
