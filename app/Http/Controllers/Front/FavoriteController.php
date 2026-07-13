<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Shop\Product;
use App\Support\Presenters\ProductCardPresenter;
use App\Support\GuestFavoritesStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Shop\Client;
use App\Models\Pages;
class FavoriteController extends Controller
{
    /** Список избранных (и для гостя, и для авторизованного) */
    public function index(Request $request)
    {
        $locale = app()->getLocale();
        $ids    = $this->favoriteIds();
        $page = Pages::query()->where('slug', 'favorites')->first();
      //  dd($ids);
        if (empty($ids)) {
            $categorySections = [[
                'title' => (function_exists('st') ? st('menu.favorites','Обране') : 'Обране'),
                'items' => collect(),   // пусто
                'slug'  => 'favorites',
            ]];

            return view(front_view('pages.catalog.category'), compact('categorySections', 'page'));
        }

        $items = (new ProductCardPresenter($locale, null, true))->collection(
            Product::withListingCardRelations()
                ->active()->cardListingSelect()->MainProduct()
                ->whereIn('id', $ids)   // фильтр есть всегда
                ->orderBy('sort')
                ->get()
        );

        $categorySections = [[
            'title' => (function_exists('st') ? st('menu.favorites','Обране') : 'Обране'),
            'items' => $items,
            'slug'  => 'favorites',
        ]];
        $favoriteIds = $ids; // <<< ВАЖНО
        return view(front_view('pages.catalog.category'), compact('categorySections', 'favoriteIds', 'page'));
    }


    /** Добавить/убрать из избранного (поддерживает гостей и авторизованных) */
    public function toggle(Request $request, Product $product)
    {
        if ($client = $this->currentClient()) {
            $pivot  = $client->favorites();
            $exists = $pivot->where('product_id', $product->id)->exists();

            $exists ? $pivot->detach($product->id) : $pivot->attach($product->id);
            
            // Возвращаем обновленное количество
            $ids = $this->favoriteIds();
            return response()->json([
                'status' => $exists ? 'removed' : 'added',
                'qty' => count($ids)
            ]);
        }

        // гость — храним в сессии
        $list = collect(GuestFavoritesStore::idsFromRequest());
        $wasInList = $list->contains($product->id);

        if ($wasInList) {
            $list = $list->reject(fn($id) => $id === $product->id)->values();
        } else {
            $list->push($product->id);
            $list = $list->unique()->values();
        }

        GuestFavoritesStore::queueIds($list->all());
        
        // Возвращаем обновленное количество
        return response()->json([
            'status' => $wasInList ? 'removed' : 'added',
            'qty' => $list->count()
        ]);
    }

    /** Получить информацию об избранном (количество) */
    public function info()
    {
        $ids = $this->favoriteIds();
        return response()->json(['qty' => count($ids)]);
    }

    /** Унифицированно получить массив ID избранных товаров */
    /** Получить список ID избранных товаров (для клиента или гостя) */


    /** Получить список ID избранных товаров (для клиента или гостя) */
    private function favoriteIds(): array
    {
        if ($client = $this->currentClient()) {
            return DB::table('bs_favorites')
                ->where('client_id', $client->id)
                ->pluck('product_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
        }

        // гость — из сессии
        return GuestFavoritesStore::idsFromRequest();
    }
    private function currentClient(): ?Client
    {
        $u = auth('client')->user();
        if ($u instanceof Client) return $u;

        $u = auth()->user();            // это guard('web') по умолчанию
        return $u instanceof Client ? $u : null;
    }


}
