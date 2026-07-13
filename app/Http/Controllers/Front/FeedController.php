<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Shop\Product;
use App\Models\Shop\ProductCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    public function esputnikProducts(): Response
    {
        $locale = app()->getLocale();

        $products = Product::query()
            ->active()
            ->where(function ($query) {
                $query->whereNull('is_imported')
                    ->orWhere('is_imported', false);
            })
            ->select([
                'id',
                'parent_id',
                'category_id',
                'sku',
                'slug',
                'title',
                'description',
                'price',
                'in_stock',
                'main_image',
                'is_new',
                'code2',
            ])
            ->with([
                'parent:id,category_id,sku,slug,title',
                'mainCategory:id,parent_id,slug,title',
                'mainCategory.parent:id,parent_id,slug,title',
                'parent.mainCategory:id,parent_id,slug,title',
                'parent.mainCategory.parent:id,parent_id,slug,title',
            ])
            ->orderBy('parent_id')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $items = $products->map(function (Product $product) use ($locale): array {
            $feedProduct = $product->parent_id ? $product->parent : $product;
            $category = $product->parent_id ? $product->parent?->mainCategory : $product->mainCategory;

            $title = $this->localizedValue($product, 'title', $locale);
            $description = $this->localizedValue($feedProduct ?? $product, 'description', $locale);
            $categoryPath = $this->categoryPath($category, $locale);

            return [
                'id' => $this->productKey($product),
                'title' => $title !== '' ? $title : $this->productKey($product),
                'description' => $description,
                'link' => $this->productUrl($feedProduct ?? $product, $locale),
                'image_link' => $product->main_image_url ?: $product->image_url,
                'condition' => 'new',
                'availability' => $product->in_stock ? 'in stock' : 'out of stock',
                'price' => number_format((float) $product->price, 2, '.', '') . ' UAH',
                'new' => $product->is_new ? '1' : '0',
                'google_product_category' => $categoryPath,
                'product_type' => $categoryPath,
                'item_group_id' => $product->parent_id ? $this->productKey($product->parent) : null,
            ];
        });

        return response()
            ->view(front_view('feeds.esputnik-products'), [
                'items' => $items,
                'feedTitle' => config('app.name', 'MyAdmin') . ' - eSputnik Products',
                'feedLink' => url('/'),
                'feedDescription' => 'eSputnik product feed',
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function localizedValue(Product|ProductCategory $model, string $field, string $locale): string
    {
        $value = method_exists($model, 'getTranslation')
            ? ($model->getTranslation($field, $locale, false)
                ?? $model->getTranslation($field, 'uk', false)
                ?? $model->getTranslation($field, 'ru', false)
                ?? $model->getTranslation($field, 'en', false))
            : data_get($model, $field);

        if (is_array($value)) {
            $value = reset($value) ?: '';
        }

        return trim(strip_tags((string) $value));
    }

    private function productKey(Product $product): string
    {
        return trim((string) ($product->code2 ?: $product->sku ?: $product->id));
    }

    private function productUrl(Product $product, string $locale): string
    {
        $categorySlug = $product->mainCategory?->slug;
        if (! $categorySlug) {
            return url('/');
        }

        $routeName = in_array($locale, ['ru', 'en'], true) ? 'localized.product.show' : 'product.show';
        $params = [
            'categorySlug' => $categorySlug,
            'itemSlug' => $product->slug,
        ];

        if ($routeName === 'localized.product.show') {
            $params = ['locale' => $locale] + $params;
        }

        return route($routeName, $params);
    }

    private function categoryPath(?ProductCategory $category, string $locale): string
    {
        if (! $category) {
            return 'Products';
        }

        $parts = [];
        $current = $category;

        while ($current) {
            $title = $this->localizedValue($current, 'title', $locale);
            if ($title !== '') {
                array_unshift($parts, $title);
            }

            $current = $current->parent;
        }

        return $parts !== [] ? implode(' > ', $parts) : 'Products';
    }
}
