<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Shop\Product;
use App\Models\Shop\ProductCategory;
use App\Support\Presenters\ProductCardPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class DeliveryController
{
    public function __invoke()
    {
        return view(front_view('pages.delivery'));
    }
}
