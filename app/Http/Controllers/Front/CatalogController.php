<?php

namespace App\Http\Controllers\Front;
use App\Support\Traits\HasCatalogFilters;
use App\Http\Controllers\Controller;
use App\Models\Shop\Client;
use App\Models\Shop\Product;
use App\Models\Shop\ProductCategory;
use App\Support\Presenters\ProductCardPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Models\Shop\Characteristic;


class CatalogController extends Controller
{
    use HasCatalogFilters;

    public function show(string $slug)
    {
        $locale = app()->getLocale();
        $pageTitle = null;

        $ids    = $this->favoriteIds();
        // 0) Виртуальные слаги: без категории, просто подбираем товары по скоупам
        if ($slug === 'pies_hits' || $slug === 'pies_news') {
            // Заголовок секции (можно заменить на st_value(...) если уже подключили словарь)
            $title = $slug === 'pies_hits'
                ? (function_exists('st') ? st('menu.hits','Хіти') : 'Хіти')
                : (function_exists('st') ? st('menu.news','Новинки') : 'Новинки');

            $q = Product::withListingCardRelations()
                ->active()->cardListingSelect()->MainProduct()
                ->where(function (Builder $w) {
                    $w->whereNull('is_imported')
                        ->orWhere('is_imported', false);
                });
            $this->applyFilters($q, request());
            $this->applySort($q, request());


            // ⬇️ Скоупы под виртуальные слаги
            if ($slug === 'pies_hits') {
                $q->hit();   // scopeHit()
            } else {
                $q->new();   // scopeNew() — если у тебя называется иначе (news / isNew), замени тут
            }

            $items = (new ProductCardPresenter($locale, null, true))->collection($q->get());
            $items = $this->sortCardCollection($items, request());
            $categorySections = [[
                'title' => $title,
                'items' => $items,
                'slug'  => $slug,
            ]];

            // границы цен для виртуального слага (по хитам/новинкам)
            [$priceMin, $priceMax] = $this->getPriceBounds($slug);
            $filterCharacteristicGroups = $this->getFilterCharacteristics();
            return view(front_view('pages.catalog.category'), [
                'categorySections' => $categorySections,
                'favoriteIds'      => $ids,       // чтобы вьюхе было одинаково
                'priceMin'         => $priceMin,
                'priceMax'         => $priceMax,
                'filterCharacteristicGroups'=> $filterCharacteristicGroups,
                'category'         => null,
            ]);
        }

        // 1) Обычный путь: реальная категория по slug

        $parent = ProductCategory::query()
            ->where('slug', $slug)
            ->first();

        $children = collect();

        if ($parent) {
            $children = $parent->children()
                ->orderBy('order')   // если поля нет — можно убрать
                ->orderBy('id')
                ->get();

            if ($children->isNotEmpty()) {
                $pageTitle = method_exists($parent, 'getTranslation')
                    ? ($parent->getTranslation('title', $locale) ?: $parent->getTranslation('title', 'uk'))
                    : (data_get($parent, "title.$locale") ?? data_get($parent, 'title.uk'));
            }
        }

// 2) Собираем ассоциативный массив slug => title (локализовано)
        $homeCategorySlugs = $children->mapWithKeys(function (ProductCategory $cat) use ($locale) {
            // если используешь spatie/translatable
            $title = method_exists($cat, 'getTranslation')
                ? ($cat->getTranslation('title', $locale) ?? $cat->title)
                : $cat->title;

            return [$cat->slug => $title];
        })->all();

        if (empty($homeCategorySlugs)){
            $homeCategorySlugs = (function (?ProductCategory $cat) use ($locale) {
                if (!$cat) return [];

                $title = method_exists($cat, 'getTranslation')
                    ? ($cat->getTranslation('title', $locale) ?? $cat->title)
                    : $cat->title;

                return [$cat->slug => $title];
            })($parent);
        }

        $sectionSlugs = array_keys($homeCategorySlugs);

        $hasFilters = request()->has('menu') ||
            request()->has('chars') ||
            request()->filled('price_min') ||
            request()->filled('price_max');
        $hasSort = request()->filled('sort');
        $useFlatResults = $hasFilters || $hasSort;

        if ($useFlatResults) {
            $q = Product::withListingCardRelations()
                ->active()->cardListingSelect()->MainProduct()
                ->where(function (Builder $w) {
                    $w->whereNull('is_imported')
                        ->orWhere('is_imported', false);
                });

            $q->where(function ($qq) use ($sectionSlugs, $slug) {
                $slugs = !empty($sectionSlugs) ? $sectionSlugs : [$slug];

                foreach ($slugs as $sectionSlug) {
                    $qq->orWhereHas('categories', fn ($qqq) => $qqq->where('slug', $sectionSlug))
                        ->orWhereHas('mainCategory', fn ($qqq) => $qqq->where('slug', $sectionSlug));
                }
            });

            if ($hasFilters) {
                $this->applyFilters($q, request());
            }

            $this->applySort($q, request());

            $items = $q->get();
            $items = (new ProductCardPresenter($locale, null, true))->collection($items);
            $items = $this->sortCardCollection($items, request());

            [$priceMin, $priceMax] = $this->getPriceBounds($slug, $sectionSlugs);
            $filterCharacteristicGroups = $this->getFilterCharacteristics($sectionSlugs);

            return view(front_view('pages.catalog.category'), [
                'categorySections' => [],
                'items' => $items,
                'favoriteIds' => $ids,
                'priceMin' => $priceMin,
                'priceMax' => $priceMax,
                'filterCharacteristicGroups' => $filterCharacteristicGroups,
                'category' => $parent,
                'pageTitle' => $pageTitle,
            ]);
        }

      /*  $category = ProductCategory::query()
            ->where('slug', $slug)
            ->firstOrFail();*/

        $q = Product::withListingCardRelations()
            ->active()->cardListingSelect()->MainProduct()
            ->with(['mainCategory', 'categories'])
            ->where(function (Builder $w) {
                $w->whereNull('is_imported')
                    ->orWhere('is_imported', false);
            })
            ->where(function (Builder $qq) use ($sectionSlugs): void {
                foreach ($sectionSlugs as $sectionSlug) {
                    $qq->orWhereHas('categories', fn (Builder $related) => $related->where('slug', $sectionSlug))
                        ->orWhereHas('mainCategory', fn (Builder $related) => $related->where('slug', $sectionSlug));
                }
            });

        $this->applySort($q, request());

        $allItems = $q->get();

        $presentedItems = (new ProductCardPresenter($locale, null, true))->collection($allItems);

        $groupedItems = collect($presentedItems)->groupBy(function (array $item) use ($sectionSlugs): string {
            $mainSlug = (string) ($item['category_slug'] ?? '');
            if ($mainSlug !== '' && in_array($mainSlug, $sectionSlugs, true)) {
                return $mainSlug;
            }

            foreach ((array) ($item['all_category_slugs'] ?? []) as $slug) {
                if (in_array((string) $slug, $sectionSlugs, true)) {
                    return (string) $slug;
                }
            }

            return $sectionSlugs[0] ?? 'misc';
        });

        $favoriteIds = $ids;
        $categorySections = collect($homeCategorySlugs)->map(function ($title, $sectionSlug) use ($groupedItems) {
            $items = $groupedItems->get($sectionSlug, collect())->values();

            return [
                'title' => $title,
                'items' => $items,
                'slug' => $sectionSlug,
            ];
        })->filter(fn (array $section): bool => collect($section['items'])->isNotEmpty())->values()->all();

        // Границы цен для обычной категории
        [$priceMin, $priceMax] = $this->getPriceBounds($slug, $sectionSlugs);
        $filterCharacteristicGroups = $this->getFilterCharacteristics($sectionSlugs);
      //  dd($filterCharacteristicGroups);
        return view(front_view('pages.catalog.category'), [
            'categorySections' => $categorySections,
            'favoriteIds'      => $favoriteIds,
            'priceMin'         => $priceMin,
            'priceMax'         => $priceMax,
            'filterCharacteristicGroups'=> $filterCharacteristicGroups,
            'category'         => $parent,
            'pageTitle'        => $pageTitle,
        ]);
    }

    public function filter(Request $request)
    {
        $locale = app()->getLocale();
        $favoriteIds = $this->favoriteIds();      // 👈 добавили
        $menuSlugs = collect((array) $request->input('menu', []))
            ->filter()
            ->map(fn ($slug) => (string) $slug)
            ->values()
            ->all();

        $q = Product::withListingCardRelations()
            ->with(['mainCategory', 'categories'])
            ->active()
            ->cardListingSelect()
            ->MainProduct()
            ->where(function (Builder $w) {
                $w->whereNull('is_imported')
                    ->orWhere('is_imported', false);
            });

        $this->applyFilters($q, $request);
        $this->applySort($q, $request);

        $items = $q->get();

        if ($items->isEmpty()) {
            // границы цен и характеристики тоже посчитаем
            [$priceMin, $priceMax] = $this->getPriceBounds('all', $menuSlugs);
            $filterCharacteristicGroups = $this->getFilterCharacteristics($menuSlugs);

            return view(front_view('pages.filter'), [
                'title'   => function_exists('st') ? st('filter.title', 'Результати фільтру') : __('Результати фільтру'),
                'items'   => collect(),
                'filters' => $request->all(),
                'favoriteIds' => $favoriteIds,
                'priceMin'    => $priceMin,
                'priceMax'    => $priceMax,
                'filterCharacteristicGroups' => $filterCharacteristicGroups,
            ]);
        }

        $cards = (new ProductCardPresenter($locale, null, true))->collection($items);
        $cards = $this->sortCardCollection($cards, $request);

        [$priceMin, $priceMax] = $this->getPriceBounds('all', $menuSlugs);
        $filterCharacteristicGroups = $this->getFilterCharacteristics($menuSlugs);

        return view(front_view('pages.filter'), [
            'title'   => function_exists('st') ? st('filter.title', 'Результати фільтру') : __('Результати фільтру'),
            'items'   => $cards,
            'filters' => $request->all(),
            'favoriteIds' => $favoriteIds,
            'priceMin'    => $priceMin,
            'priceMax'    => $priceMax,
            'filterCharacteristicGroups' => $filterCharacteristicGroups,
        ]);
    }


    /**
     * Применить сортировку к запросу товаров по параметру ?sort=
     */
    protected function applySort(Builder $query, Request $request): Builder
    {
        $sort = $request->query('sort', 'popular');

        switch ($sort) {
            case 'price_asc':
                // сортировка по цене ↑
                $query->orderBy('price', 'asc');
                break;

            case 'price_desc':
                // сортировка по цене ↓
                $query->orderBy('price', 'desc');
                break;

            case 'discount_asc':
                // сначала товары со скидкой, затем по проценту скидки ↑
                $query->orderByRaw("CASE WHEN old_price IS NOT NULL AND old_price > 0 AND old_price > price THEN 0 ELSE 1 END ASC")
                    ->orderByRaw("CASE WHEN old_price IS NOT NULL AND old_price > 0 AND old_price > price THEN ((old_price - price) / old_price) * 100 ELSE 0 END ASC");
                break;

            case 'discount_desc':
                // сначала товары со скидкой, затем по проценту скидки ↓
                $query->orderByRaw("CASE WHEN old_price IS NOT NULL AND old_price > 0 AND old_price > price THEN 0 ELSE 1 END ASC")
                    ->orderByRaw("CASE WHEN old_price IS NOT NULL AND old_price > 0 AND old_price > price THEN ((old_price - price) / old_price) * 100 ELSE 0 END DESC");
                break;

            case 'new':
                // Сначала товары с флагом "новинка", затем по дате, затем стабильный порядок
                $query->orderByDesc('is_new')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('sort', 'asc');
                break;

            case 'popular':
            default:
                // Базовый порядок каталога соответствует сортировке в админке.
                $query->orderBy('sort', 'asc')
                    ->orderBy('id', 'asc');
                break;
        }

        return $query;
    }
    /**
     * Сортировка уже «готовых» карточек (массивов) после презентера.
     */
    protected function sortCardCollection($items, Request $request)
    {
        // если пришёл массив — оборачиваем в коллекцию
        if (! $items instanceof Collection) {
            $items = collect($items);
        }

        $sort = $request->query('sort', 'popular');

        $items = match ($sort) {
            // Цена ↑
            'price_asc' => $items->sortBy(function ($p) {
                return $p['price'] ?? $p['final_price'] ?? $p['min_price'] ?? 0;
            }),

            // Цена ↓
            'price_desc' => $items->sortByDesc(function ($p) {
                return $p['price'] ?? $p['final_price'] ?? $p['min_price'] ?? 0;
            }),

            // Знижка ↑
            'discount_asc' => $items->sortBy(fn ($p) => [
                $this->resolveDiscountPercentForCard($p) > 0 ? 0 : 1,
                $this->resolveDiscountPercentForCard($p),
            ]),

            // Знижка ↓
            'discount_desc' => $items->sortBy(fn ($p) => [
                $this->resolveDiscountPercentForCard($p) > 0 ? 0 : 1,
                -1 * $this->resolveDiscountPercentForCard($p),
            ]),

            // Новинки/Популярні: сохраняем SQL-порядок
            'new' => $items,

            // Популярні — базовый порядок (по sort)
            default => $items,
        };

        return $items->values(); // нормализуем ключи 0..N
    }

    protected function resolveDiscountPercentForCard(array $product): float
    {
        $price = (float) ($product['price'] ?? $product['final_price'] ?? $product['min_price'] ?? 0);
        $oldPrice = (float) ($product['old_price'] ?? $product['price_old'] ?? $price);

        if ($oldPrice <= 0 || $oldPrice <= $price || $price <= 0) {
            return 0.0;
        }

        return (($oldPrice - $price) / $oldPrice) * 100;
    }
}
