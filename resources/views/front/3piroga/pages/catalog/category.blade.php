@extends(front_view('layouts.app'))

@php
    /** @var \App\Models\Shop\ProductCategory|null $category */
    $locale = app()->getLocale();

    $defaultTitle = '';

    $seoTitle = '';
    $seoDescription = '';
    $seoKeywords = '';

    if (!empty($page)) {
        $fallback = config('translatable.fallback_locale', 'uk');
        $seoTitle = (string) (($page->meta_title[$locale] ?? null)
            ?: ($page->meta_title[$fallback] ?? null)
            ?: '');
        $seoDescription = (string) (($page->meta_description[$locale] ?? null)
            ?: ($page->meta_description[$fallback] ?? null)
            ?: '');
        $seoKeywords = (string) (($page->meta_keywords[$locale] ?? null)
            ?: ($page->meta_keywords[$fallback] ?? null)
            ?: '');
    }

    if ($seoTitle === '' && !empty($category)) {
        if (method_exists($category, 'getTranslation')) {
            $seoTitle = (string) ($category->getTranslation('seo_title', $locale)
                ?: $category->getTranslation('seo_title', 'uk')
                ?: '');
            $seoDescription = (string) ($category->getTranslation('seo_description', $locale)
                ?: $category->getTranslation('seo_description', 'uk')
                ?: '');
            $seoKeywords = (string) ($category->getTranslation('seo_keywords', $locale)
                ?: $category->getTranslation('seo_keywords', 'uk')
                ?: '');

            if (trim($seoTitle) === '') {
                $seoTitle = (string) ($category->getTranslation('title', $locale)
                    ?: $category->getTranslation('title', 'uk')
                    ?: '');
            }

            if (trim($seoDescription) === '') {
                $seoDescription = (string) ($category->getTranslation('description', $locale)
                    ?: $category->getTranslation('description', 'uk')
                    ?: '');
            }
        } else {
            $seoTitle = (string) (data_get($category, "seo_title.$locale")
                ?? data_get($category, 'seo_title.uk')
                ?? '');
            $seoDescription = (string) (data_get($category, "seo_description.$locale")
                ?? data_get($category, 'seo_description.uk')
                ?? '');
            $seoKeywords = (string) (data_get($category, "seo_keywords.$locale")
                ?? data_get($category, 'seo_keywords.uk')
                ?? '');

            if (trim($seoTitle) === '') {
                $seoTitle = (string) (data_get($category, "title.$locale")
                    ?? data_get($category, 'title.uk')
                    ?? '');
            }

            if (trim($seoDescription) === '') {
                $seoDescription = (string) (data_get($category, "description.$locale")
                    ?? data_get($category, 'description.uk')
                    ?? '');
            }
        }
    }

    $seoTitle = trim(html_entity_decode($seoTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($seoTitle === '') {
        $seoTitle = trim((string) ($pageTitle ?? ''));
    }

    if ($seoTitle === '' && !empty($categorySections) && is_array($categorySections)) {
        $firstSection = $categorySections[0] ?? null;
        if (is_array($firstSection)) {
            $seoTitle = trim((string) ($firstSection['title'] ?? ''));
        }
    }

    if ($seoTitle === '') {
        $seoTitle = st('home.delivery_of_ossetian_pies', 'Доставка осетинських пирогів у Києві');
    }

    $seoKeywords = trim(html_entity_decode($seoKeywords, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $seoKeywords = trim(preg_replace('/\s+/u', ' ', strip_tags($seoKeywords)));

    $seoDescription = trim(html_entity_decode($seoDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($seoDescription !== '') {
        $seoDescription = (string) str($seoDescription)->markdown()->sanitizeHtml();
        $seoDescription = trim(preg_replace('/\s+/u', ' ', strip_tags($seoDescription)));
        if (mb_strlen($seoDescription) > 250) {
            $seoDescription = rtrim(mb_substr($seoDescription, 0, 247)) . '...';
        }
    }

    $eSputnikCategoryKey = trim((string) ($pageTitle ?? ''));
    if ($eSputnikCategoryKey === '' && !empty($category)) {
        $eSputnikCategoryKey = method_exists($category, 'getTranslation')
            ? (string) ($category->getTranslation('title', $locale) ?: $category->getTranslation('title', 'uk') ?: '')
            : (string) (data_get($category, "title.$locale") ?? data_get($category, 'title.uk') ?? '');
    }
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@if($seoKeywords !== '')
    @section('meta_keywords', $seoKeywords)
@endif

@section('og_title', $seoTitle)
@section('og_description', $seoDescription)
@section('twitter_title', $seoTitle)
@section('twitter_description', $seoDescription)

@section('content')
    <div class=" mx-auto desk:w-[1198px] w-[357px] md:w-[736px] max-w-full">
        <div x-data="{ filterOpen: false }">
        <div class="flex items-center justify-between mb-4">
            <button type="button"
                    @click="filterOpen = true"
                    class="w-10 md:w-[132px] h-10 rounded-[12px] border border-[#E5E7EB] bg-white
                       px-3 inline-flex items-center gap-2 justify-center">
                <img src="{{ asset('images/filter.svg') }}" alt="" class="w-[22px] h-[19px]" aria-hidden="true">
                <span class="hidden md:block font-bold text-[16px] leading-none text-[#19191A]">
                 {{ st('all.filter','Фільтр') }}
            </span>
            </button>
            <x-ui.sort-dropdown  />
        </div>

        <section class="max-w-screen-xl mx-auto ">
            @if(!empty($pageTitle))
                <div class="mb-6 md:mb-8">
                    <x-product.section-title>{{ $pageTitle }}</x-product.section-title>
                </div>
            @endif

            @if(!empty($items))
                <div class="grid grid-cols-1 md:grid-cols-2 desk:grid-cols-3 gap-4 desk:gap-12 md:gap-8 mt-4">
                    @foreach($items as $p)
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
            @else
                @foreach($categorySections as $section)
                   <div class="space-y-14 mt-4">
                        @if(!empty($pageTitle))
                            <x-product.section :title="$section['title']"
                                               title-as="h3"
                                               title-size="catalog-subcategory"
                                               :title-underline="false"
                                               :favoriteIds="$favoriteIds ?? []"
                                               :items="$section['items']" />
                        @else
                            <x-product.section :title="$section['title']"
                                               :favoriteIds="$favoriteIds ?? []"
                                               :items="$section['items']" />
                        @endif
                </div>
                @endforeach
            @endif
        </section>

        @php
            /** @var \App\Models\Shop\ProductCategory|null $category */
            $locale = app()->getLocale();

            $descriptionTitle = '';
            $descriptionRaw = '';

            if (!empty($category)) {
                if (method_exists($category, 'getTranslation')) {
                    $descriptionTitle = (string) ($category->getTranslation('description_title', $locale)
                        ?: $category->getTranslation('description_title', 'uk')
                        ?: '');
                    $descriptionRaw = (string) ($category->getTranslation('description', $locale)
                        ?: $category->getTranslation('description', 'uk')
                        ?: '');

                    if (trim($descriptionTitle) === '') {
                        $descriptionTitle = (string) ($category->getTranslation('title', $locale)
                            ?: $category->getTranslation('title', 'uk')
                            ?: '');
                    }
                } else {
                    $descriptionTitle = (string) (data_get($category, "description_title.$locale")
                        ?? data_get($category, 'description_title.uk')
                        ?? '');
                    $descriptionRaw = (string) (data_get($category, "description.$locale")
                        ?? data_get($category, 'description.uk')
                        ?? '');

                    if (trim($descriptionTitle) === '') {
                        $descriptionTitle = (string) (data_get($category, "title.$locale")
                            ?? data_get($category, 'title.uk')
                            ?? '');
                    }
                }
            }

            $descriptionTitle = trim(html_entity_decode((string) $descriptionTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $descriptionTitle = trim(preg_replace('/\s+/u', ' ', strip_tags($descriptionTitle)));

            $descriptionRaw = trim(html_entity_decode((string) $descriptionRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $descriptionHtml = $descriptionRaw !== ''
                ? (string) str($descriptionRaw)->markdown()->sanitizeHtml()
                : '';
        @endphp

        @if($descriptionTitle !== '' || $descriptionHtml !== '')
            <section class="mt-[80px] md:mt-[120px] bg-white overflow-hidden">
                <div class="desk:p-[30px] lg:p-[50px]">
                    @if($descriptionTitle !== '')
                        <h2 class="text-[40px] leading-tight font-bold text-center">
                            {{ $descriptionTitle }}
                        </h2>
                    @endif

                    @if($descriptionHtml !== '')
                        <div class="prose max-w-none mt-4 text-[#333333]">
                            {!! $descriptionHtml !!}
                        </div>
                    @endif
                </div>
            </section>
        @endif

            {{-- Окно фильтра --}}
            @include(front_view('product.filter-panel'))
    </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.eSputnikTrackCategoryPage(@json($eSputnikCategoryKey));
            });
        </script>
    @endpush
@endsection
