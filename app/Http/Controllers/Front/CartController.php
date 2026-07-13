<?php
namespace App\Http\Controllers\Front;

use App\Services\CartService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product;
use App\Support\Presenters\ProductCardPresenter;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

// app/Http/Controllers/Front/CartController.php

    public function add(Request $r)
    {
        // Принимаем и product_id, и старое поле product
        $pid = (int) $r->input('product_id', 0);
        if (! $pid) {
            $pid = (int) $r->input('product', 0);
        }

        if (! $pid) {
            return back()->with('cart_error', 'Не передан идентификатор товара.');
        }

        $qty   = (int) $r->input('qty', 1);
        $price = $r->has('price') ? (float) $r->input('price') : null;

        // Если передан параметр set=true, устанавливаем абсолютное количество
        if ($r->boolean('set', false)) {
            $payload = $this->cart->setQty($pid, $qty, $price);
        } else {
            // Иначе добавляем/уменьшаем количество (может быть отрицательным для уменьшения)
            // CartService::add поддерживает отрицательные значения для уменьшения количества
            $payload = $this->cart->add($pid, $qty, $price);
        }

        // Для AJAX/JSON-запросов возвращаем JSON как раньше
        if ($r->expectsJson() || $r->wantsJson() || $r->ajax()) {
            return response()->json($payload);
        }

        // Для обычной формы (карточка товара) просто возвращаемся назад
        return back()->with('cart', $payload);
    }
public function page()
{
    $locale = app()->getLocale();
    $info = $this->cart->info();
    $relatedQuery = Product::withListingCardRelations()
        ->active()
        ->cardListingSelect()
        ->MainProduct()
        ->hit()
        ->orderBy('sort');
    $related = (new ProductCardPresenter($locale, null, true))->collection($relatedQuery->get());

    return view(front_view('cart.index'), [
        'items' => $info['items'] ?? [],
        'qty'   => (int)($info['qty'] ?? 0),
        'total' => (float)($info['total'] ?? $info['total_price'] ?? 0),
        'related' => $related,
    ]);
}

public function remove(Request $r)
{
    $pid = $r->integer('product_id');
    $all = $r->boolean('all', false);

    $payload = $this->cart->remove($pid, $all);

    return response()->json($payload);
}

// HTML для сайдбара — ОТДЕЛЬНО
public function sidebar()
{
    $info = $this->cart->info();
    return view(front_view('partials.cart-sidebar'), [
        'items' => $info['items'] ?? [],
        'qty'   => (int)($info['qty'] ?? 0),
        'total' => (float)($info['total'] ?? $info['total_price'] ?? 0),
    ]);
}
public function info()
{
    return response()->json($this->cart->info());
}
}
