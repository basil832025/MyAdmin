<?php

namespace App\Http\Controllers\Front;
use App\Support\Traits\HasCatalogFilters;
use App\Models\Blog;
use App\Models\Banner;
use App\Models\Pages;
use App\Models\Shop\Product;
use App\Http\Controllers\Controller;
use App\Models\Shop\ProductCategory;
use App\Services\CatalogCacheService;
use App\Support\Presenters\ProductCardPresenter;
use App\Services\SiteTemplates\SiteTemplateRenderer;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    use HasCatalogFilters;
    public function index()
    {
        $page = Pages::query()->where('slug', 'home')->first();

        $favoriteIds = $this->favoriteIds();
        [$priceMin, $priceMax] = $this->getPriceBounds('all');
        $filterCharacteristicGroups = $this->getFilterCharacteristics();

        $locale = app()->getLocale(); // 'uk' у тебя по умолчанию
        // ===== БАННЕРЫ =====
        $now = now();

        $banners = Banner::query()
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort')
            ->get();

        // Helper: apply common main-page filters.
        $applyMainPageBase = function ($q): void {
            $q->where(function ($w) {
                $w->whereNull('bs_products.is_imported')
                    ->orWhere('bs_products.is_imported', false);
            });
        };

        $excludeRootIds = [];

        // 0) АКЦИИ (is_promo + is_home) — должны быть первыми
        $promoQuery = Product::withListingCardRelations()
            ->active()->home()->where('bs_products.is_promo', 1)->cardListingSelect()->MainProduct()->Pie();
        $applyMainPageBase($promoQuery);
        $this->applySort($promoQuery, request());
        $promo_products = $promoQuery->get();
        $promo = (new ProductCardPresenter($locale, null, true))->collection($promo_products);
        $excludeRootIds = array_values(array_unique(array_merge($excludeRootIds, $promo_products->pluck('id')->all())));

        // 1) ХІТИ (is_hit + is_home)
        $hitsQuery = Product::withListingCardRelations()
            ->active()->home()->hit()->cardListingSelect()->MainProduct()
            ->when(!empty($excludeRootIds), fn ($q) => $q->whereNotIn('bs_products.id', $excludeRootIds));
        $applyMainPageBase($hitsQuery);
        if (in_array(request()->query('sort', 'popular'), [null, '', 'popular'], true)) {
            $hitsQuery
                ->leftJoin('bs_product_categories as main_category', 'main_category.id', '=', 'bs_products.category_id')
                ->leftJoin('bs_product_categories as root_category', 'root_category.id', '=', 'main_category.parent_id')
                ->orderByRaw('COALESCE(root_category.`order`, main_category.`order`, 999999) asc')
                ->orderByRaw('CASE WHEN root_category.id IS NULL THEN 0 ELSE COALESCE(main_category.`order`, 0) END asc')
                ->orderBy('bs_products.sort', 'asc')
                ->orderBy('bs_products.id', 'asc');
        } else {
            $this->applySort($hitsQuery, request());
        }
        $hits_products = $hitsQuery->get();
        $hits = (new ProductCardPresenter($locale, null, true))->collection($hits_products);
        $excludeRootIds = array_values(array_unique(array_merge($excludeRootIds, $hits_products->pluck('id')->all())));

        // 2) НОВИНКИ (is_new + is_home)
        $newsQuery = Product::withListingCardRelations()
            ->active()->home()->new()->cardListingSelect()->MainProduct()
            ->when(!empty($excludeRootIds), fn ($q) => $q->whereNotIn('bs_products.id', $excludeRootIds));
        $applyMainPageBase($newsQuery);
        if (in_array(request()->query('sort', 'popular'), [null, '', 'popular'], true)) {
            $newsQuery
                ->leftJoin('bs_product_categories as main_category', 'main_category.id', '=', 'bs_products.category_id')
                ->leftJoin('bs_product_categories as root_category', 'root_category.id', '=', 'main_category.parent_id')
                ->orderByRaw('COALESCE(root_category.`order`, main_category.`order`, 999999) asc')
                ->orderByRaw('CASE WHEN root_category.id IS NULL THEN 0 ELSE COALESCE(main_category.`order`, 0) END asc')
                ->orderBy('bs_products.sort', 'asc')
                ->orderBy('bs_products.id', 'asc');
        } else {
            $this->applySort($newsQuery, request());
        }
        $news_products = $newsQuery->get();
        $news = (new ProductCardPresenter($locale, null, true))->collection($news_products);
        $excludeRootIds = array_values(array_unique(array_merge($excludeRootIds, $news_products->pluck('id')->all())));

        $parentSlug = 'pies';

        $pickCategoryTitle = function (ProductCategory $cat) use ($locale): string {
            $title = method_exists($cat, 'getTranslation')
                ? ($cat->getTranslation('title', $locale) ?? $cat->title)
                : $cat->title;
            return is_string($title) ? (string) $title : '';
        };

        $catalogCache = app(CatalogCacheService::class);

        $buildHomeCategorySection = function (string $slug, string $title) use ($locale, $applyMainPageBase, &$excludeRootIds, $catalogCache): ?array {
            $excludedIds = $excludeRootIds;
            $cacheKey = $catalogCache->key('home_category_section', array_merge([$slug], $excludedIds), $locale);

            $payload = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($slug, $locale, $applyMainPageBase, $excludedIds) {
                $q = Product::withListingCardRelations()
                    ->active()->home()->cardListingSelect()->MainProduct()
                    ->where(function ($query) use ($slug) {
                        $query->whereHas('categories', fn ($qq) => $qq->where('slug', $slug))
                            ->orWhereHas('mainCategory', fn ($qq) => $qq->where('slug', $slug));
                    })
                    ->when(! empty($excludedIds), fn ($qq) => $qq->whereNotIn('bs_products.id', $excludedIds));

                $applyMainPageBase($q);
                $this->applySort($q, request());

                $models = $q->get();

                return [
                    'ids' => $models->pluck('id')->values()->all(),
                    'items' => (new ProductCardPresenter($locale, null, true))->collection($models),
                ];
            });

            $modelsIds = $payload['ids'] ?? [];
            if ($modelsIds === []) {
                return null;
            }

            $excludeRootIds = array_values(array_unique(array_merge($excludeRootIds, $modelsIds)));

            return [
                'title' => $title,
                'items' => $payload['items'] ?? [],
                'slug' => $slug,
            ];
        };

        $categorySections = [];

        // 3) Пироги по группам (только is_home)
        $pieChildren = Cache::remember($catalogCache->key('home_pies_children', [$parentSlug], $locale), now()->addMinutes(30), function () use ($parentSlug) {
            $parent = ProductCategory::query()->where('slug', $parentSlug)->first();
            if (!$parent) {
                return collect();
            }
            return $parent->children()
                ->where('is_visible', 1)
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        });

        foreach ($pieChildren as $cat) {
            /** @var ProductCategory $cat */
            $slug = (string) $cat->slug;
            if ($slug === '') {
                continue;
            }
            $section = $buildHomeCategorySection($slug, $pickCategoryTitle($cat));
            if ($section) {
                $categorySections[] = $section;
            }
        }

        // 4) Остальные группы по группам (только is_home)
        $otherRoots = Cache::remember($catalogCache->key('home_other_roots', [$parentSlug], $locale), now()->addMinutes(30), function () use ($parentSlug) {
            return ProductCategory::query()
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', -1);
                })
                ->where('is_visible', 1)
                ->where('slug', '!=', $parentSlug)
                ->orderBy('order')
                ->orderBy('id')
                ->with([
                    'children' => fn ($q) => $q->where('is_visible', 1)
                        ->orderBy('order')
                        ->orderBy('id'),
                ])
                ->get();
        });

        foreach ($otherRoots as $root) {
            /** @var ProductCategory $root */
            $children = $root->children ?? collect();

            $groups = $children->isNotEmpty() ? $children : collect([$root]);

            foreach ($groups as $cat) {
                /** @var ProductCategory $cat */
                $slug = (string) $cat->slug;
                if ($slug === '') {
                    continue;
                }
                $section = $buildHomeCategorySection($slug, $pickCategoryTitle($cat));
                if ($section) {
                    $categorySections[] = $section;
                }
            }
        }

        $homeBlog = Blog::query()
            ->published()
            ->where('slug', 'home_blog')
            ->first();

        return app(SiteTemplateRenderer::class)->render('home', front_view('home'), [
            'banners'          => $banners,
            'promo'            => $promo,
            'hits'             => $hits,
            'news'             => $news,
            'favoriteIds' => $favoriteIds,
            'priceMin'    => $priceMin,
            'priceMax'    => $priceMax,
            'filterCharacteristicGroups' => $filterCharacteristicGroups,
            'categorySections' => $categorySections,
            'homeBlog' => $homeBlog,
            'page' => $page,
        ]);
    }
}
