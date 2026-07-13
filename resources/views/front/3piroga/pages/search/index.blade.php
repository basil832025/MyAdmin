@extends(front_view('layouts.app'))

@section('title', st('search.title', 'Результати пошуку') . ($q ? ': ' . $q : ''))

@section('content')
    <div class="mx-auto desk:w-[1198px] w-[357px] md:w-[736px] max-w-full">
        
        {{-- Заголовок --}}
        <div class="mb-6">
            <h1 class="text-[26px] md:text-[32px] font-bold text-[#19191A] mb-2">
                {{ st('search.title', 'Результати пошуку') }}
                @if($q)
                    <span class="text-[#FF7500]">"{{ $q }}"</span>
                @endif
            </h1>
            @php
                $productsCount = is_array($products) ? count($products) : $products->count();
                $categoriesCount = $categories->count();
            @endphp
            @if($q && ($productsCount > 0 || $categoriesCount > 0))
                <p class="text-[#666666] text-sm">
                    {{ st('search.found', 'Знайдено') }}: 
                    @if($productsCount > 0)
                        {{ $productsCount }} {{ st('search.products_count', 'товарів') }}
                    @endif
                    @if($categoriesCount > 0)
                        @if($productsCount > 0), @endif
                        {{ $categoriesCount }} {{ st('search.categories_count', 'категорій') }}
                    @endif
                </p>
            @endif
        </div>

        @if($q === '')
            {{-- Пустой поиск --}}
            <div class="text-center py-12">
                <p class="text-[#666666] text-lg">{{ st('search.enter_query', 'Введіть запит для пошуку') }}</p>
            </div>
        @elseif($productsCount === 0 && $categoriesCount === 0)
            {{-- Ничего не найдено --}}
            <div class="text-center py-12">
                <p class="text-[#666666] text-lg">
                    {{ st('search.not_found', 'Нічого не знайдено для запиту') }} 
                    <span class="font-semibold text-[#FF7500]">"{{ $q }}"</span>
                </p>
            </div>
        @else
            {{-- Категории --}}
            @if($categoriesCount > 0)
                <section class="mb-12">
                    <h2 class="text-[22px] font-bold text-[#19191A] mb-4">
                        {{ st('search.categories', 'Категорії') }}
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($categories as $category)
                            @php
                                $loc = app()->getLocale();
                                $catUrl = in_array($loc, ['ru', 'en'], true)
                                    ? '/' . $loc . '/' . ltrim((string) $category->slug, '/')
                                    : '/' . ltrim((string) $category->slug, '/');
                            @endphp
                            <a href="{{ $catUrl }}" 
                               class="flex items-center gap-3 p-4 rounded-xl border border-[#E5E7EB] hover:border-[#FF7500] hover:bg-orange-50 transition">
                                <span class="inline-flex w-10 h-10 items-center justify-center rounded-lg bg-[#FF7500]/10 text-[#FF7500] text-xl">▦</span>
                                <div>
                                    <div class="font-semibold text-[#19191A]">{{ $category->getTranslation('title', app()->getLocale()) }}</div>
                                </div>
                                <svg class="ml-auto w-5 h-5 text-[#FF7500]" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Товары --}}
            @if($productsCount > 0)
                <section>
                    @if($categoriesCount > 0)
                        <h2 class="text-[22px] font-bold text-[#19191A] mb-6">
                            {{ st('search.products', 'Товари') }}
                        </h2>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 desk:grid-cols-3 gap-4 desk:gap-12 md:gap-8">
                        @foreach($products as $p)
                            @php
                                $pid = $p['root_id'] ?? null;
                                $isFav = $pid ? in_array($pid, $favoriteIds ?? [], true) : false;
                            @endphp
                            <x-product.card
                                :product-id="$pid"
                                :is-favorite="$isFav"
                                :title="$p['title'] ?? st('search.product_default_title', 'Товар')"
                                :url="$p['url'] ?? ''"
                                :article="$p['article'] ?? '12345'"
                                :price="$p['price'] ?? '0.00'"
                                :description="$p['card_description'] ?? ($p['description'] ?? '')"
                                :price_no_sale="$p['old_price'] ?? $p['price_no_sale'] ?? null"
                                :image="$p['main_image'] ?? '/images/no-image.svg'"
                                :characteristics="$p['characteristics'] ?? []"
                                :rows="$p['variant_rows'] ?? []"
                                :root_id="$p['root_id'] ?? null"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

    </div>
@endsection
