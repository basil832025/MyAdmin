<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\CartService;

class CartPageController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

public function index()
{
    $items  = $this->cart->items();                 // уже есть в твоём сервисе
    $info   = $this->cart->info();
    $totals = ['qty' => (int)$info['qty'], 'total_price' => (float)$info['total_price']];

    return view(front_view('cart.index'), compact('items', 'totals'));
}
}
