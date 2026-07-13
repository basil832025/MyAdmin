<?php

namespace App\Providers;

use App\Models\Shop\Product;
use App\Models\Shop\ProductCategory;
use App\Services\CatalogCacheService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Автоматически подсовываем данные в partials.menu
        View::composer([
            'partials.menu',
            'front.*.partials.menu',
            'partials.footer',
            'front.*.partials.footer',
            'product.filter-panel',
            'front.*.product.filter-panel',      // наш шаблон фильтра
            'components.menu-drawer',
            'front.*.components.menu-drawer',      // наш шаблон фильтра
          ], function ($view) {
            $locale = app()->getLocale();
            $prefix = in_array($locale, ['ru', 'en'], true) ? '/' . $locale : '';
            $items = Cache::remember(
                app(CatalogCacheService::class)->key('main_menu_items', locale: $locale),
                now()->addMinutes(30),
                function () use ($locale, $prefix): array {
                    $countsBySlug = $this->menuProductCounts();

                    $roots = ProductCategory::query()
                        ->where('is_visible', 1)
                        ->where('parent_id', -1)
                        ->orderBy('order')
                        ->with([
                            'children' => fn ($q) => $q->where('is_visible', 1)
                                ->orderBy('order')
                                ->with([
                                    'children' => fn ($q) => $q->where('is_visible', 1)
                                        ->orderBy('order')
                                        ->with('children'),
                                ]),
                        ])
                        ->get();

                    $flat = collect();

                    $walk = function ($node) use (&$walk, &$flat, $locale, $prefix, $countsBySlug) {
                        $flat->push([
                            'id' => $node->id,
                            'label' => $node->getTranslation('title', $locale),
                            'slug' => $node->slug,
                            'url' => $prefix . '/' . ltrim((string) $node->slug, '/'),
                            'order' => (int) ($node->order ?? 0),
                            'count' => (int) ($countsBySlug[$node->slug] ?? 0),
                        ]);

                        if ($node->slug == 'pies') {
                            $flat->push([
                                'id' => 5000,
                                'label' => st('menu.hits', 'Хіти'),
                                'slug' => 'pies_hits',
                                'url' => $prefix . '/pies_hits',
                                'order' => (int) ($node->order ?? 0),
                            ]);
                            $flat->push([
                                'id' => 5001,
                                'label' => st('menu.news', 'Новинки'),
                                'slug' => 'pies_news',
                                'url' => $prefix . '/pies_news',
                                'order' => (int) ($node->order ?? 0),
                            ]);
                        }

                        if ($node->relationLoaded('children') && $node->children->isNotEmpty()) {
                            foreach ($node->children as $child) {
                                $walk($child);
                            }
                        }
                    };

                    foreach ($roots as $root) {
                        $walk($root);
                    }

                    return $flat->values()->all();
                }
            );

            $currentSlug = request()->routeIs('catalog.index', 'localized.catalog.index')
                ? 'pies'
                : (request()->route('slug') ?? request()->route('categorySlug'));

            $activeIndex = null;
            foreach ($items as $i => $it) {
                if ($currentSlug === null && $it['slug'] === null) { $activeIndex = $i; break; }
                if ($currentSlug !== null && $it['slug'] === $currentSlug) { $activeIndex = $i; break; }
            }

            $MainMenuItems=$items;
            $MenuactiveIndex=$activeIndex;
            $MenuBrand='';
            // Передаём в partial
            $view->with(compact('MainMenuItems', 'MenuactiveIndex', 'MenuBrand'));
        });
    }

    protected function menuProductCounts(): array
    {
        $cacheKey = app(CatalogCacheService::class)->key('menu_product_counts');

        return Cache::remember($cacheKey, now()->addMinutes(5), function (): array {
            $products = Product::query()
                ->active()
                ->mainProduct()
                ->where(function ($query) {
                    $query->whereNull('is_imported')
                        ->orWhere('is_imported', false);
                })
                ->select(['id', 'category_id'])
                ->with([
                    'mainCategory:id,slug',
                    'categories:id,slug',
                ])
                ->get();

            $counts = [];

            foreach ($products as $product) {
                $slugs = collect([
                    $product->mainCategory?->slug,
                    ...$product->categories->pluck('slug')->all(),
                ])->filter()->unique();

                foreach ($slugs as $slug) {
                    $counts[$slug] = ($counts[$slug] ?? 0) + 1;
                }
            }

            return $counts;
        });
    }
}
