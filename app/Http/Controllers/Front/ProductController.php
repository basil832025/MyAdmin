<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Shop\Product;
use App\Models\Shop\ProductCategory;
use App\Models\Shop\ProductReview;
use App\Support\Presenters\ProductCardPresenter;
use App\Services\LoyaltyService;

class ProductController extends Controller
{
    public function show(string $categorySlug, string $productSlug)
    {
        $locale = app()->getLocale();
        $category = ProductCategory::query()->where('slug', $categorySlug)->firstOrFail();

        $product = Product::withCardRelations()
            ->cardSelect()
            ->where('slug', $productSlug)
            ->firstOrFail();
        // Список отзывов (только опубликованные)
        $reviews = ProductReview::query()
            ->published()
            ->where('product_id', $product->id)
            ->latest('created_at')
            ->paginate(10);

        // Invalid/out-of-range page should show our 404 page.
        if ($reviews->currentPage() > $reviews->lastPage() && $reviews->currentPage() > 1) {
            return response()->view(front_view('404'), [], 404);
        }

        // Агрегаты за 1 запрос
        $stats = ProductReview::query()
            ->published()
            ->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as total,
                     AVG(rating) as avg_rating,
                     SUM(rating=5) as r5,
                     SUM(rating=4) as r4,
                     SUM(rating=3) as r3,
                     SUM(rating=2) as r2,
                     SUM(rating=1) as r1')
            ->first();
        // выведим хиты для рекомендаций
        $q = Product::withListingCardRelations()
            // ->addSelect('category_id')
            ->active()->cardListingSelect()->MainProduct()->hit()
           ->orderBy('sort');
        $related = (new ProductCardPresenter($locale, null, true))->collection($q->get());
        $product = (new ProductCardPresenter($locale))->for($product);

        // Расчет процента начисления бонусов и минимальной суммы
        $loyalty = app(LoyaltyService::class);
        $rule = $loyalty->findRuleForDate(\Carbon\Carbon::now());
        $bonusPercent = ($rule && $rule->is_enabled) ? (int)$rule->earn_percent : 0;
        $minOrderSumForEarn = ($rule && $rule->is_enabled) ? (float)$rule->min_order_sum_for_earn : 0;

        return view(front_view('pages.catalog.product'), compact('product', 'category', 'related','reviews','stats', 'bonusPercent', 'minOrderSumForEarn'));
    }
}
