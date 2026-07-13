<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\PasswordResetSmsController;
use App\Http\Controllers\Auth\PhoneRegisterController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Front\BlogController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CatalogController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\ClientAddressController;
use App\Http\Controllers\Front\FavoriteController;
use App\Http\Controllers\Front\FeedController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\LiqPayController;
use App\Http\Controllers\Front\PaypartsController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\Front\ProductReviewController;
use App\Http\Controllers\Front\ReviewController;
use App\Http\Controllers\Front\SearchController;
use App\Http\Controllers\Integrations\BinotelWebhookController;
use App\Models\BlogCategory;
use App\Models\Pages;
use App\Models\Shop\Product;
use App\Models\Shop\ProductCategory;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$registerFrontRoutes = function (): void {
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::get('/feeds/esputnik-products.xml', [FeedController::class, 'esputnikProducts'])->name('feeds.esputnik.products');

Route::get('/', [HomeController::class, 'index'])
    ->name('home')
    ->defaults('page_cache_candidate', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/cart', [CartController::class, 'page'])
    ->name('cart.page')
    ->defaults('guest_stateless', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'submit'])->name('checkout.submit');
Route::post('/checkout/save-form-data', [CheckoutController::class, 'saveFormData'])->name('checkout.save-form-data');
Route::post('/checkout/apply-coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');
Route::post('/checkout/promo', [CheckoutController::class, 'updatePromo'])->name('checkout.promo');
Route::post('/checkout/payparts-options', [CheckoutController::class, 'paypartsOptions'])->name('checkout.payparts-options');
Route::post('/checkout/check-promo-conditions', [CheckoutController::class, 'checkPromoConditionsAjax'])
    ->name('checkout.check-promo-conditions');
Route::get('/checkout/{order}/pay/liqpay', [CheckoutController::class, 'payLiqPay'])->name('checkout.pay.liqpay');
Route::post('/checkout/{order}/pay/liqpay/email', [CheckoutController::class, 'saveLiqPayEmail'])
    ->name('checkout.pay.liqpay.email');
Route::get('/checkout/{order}/pay/payparts', [CheckoutController::class, 'payPayparts'])->name('checkout.pay.payparts');
Route::post('/checkout/{order}/pay/payparts/email', [CheckoutController::class, 'savePaypartsEmail'])->name('checkout.pay.payparts.email');
Route::get('/checkout/{order}/pay/payparts/status', [CheckoutController::class, 'payPaypartsStatus'])->name('checkout.pay.payparts.status');
Route::get('/filter', [CatalogController::class, 'filter'])->name('catalog.filter');
Route::post('/liqpay/callback', [LiqPayController::class, 'callback'])
    ->name('liqpay.callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::match(['get', 'post'], '/payparts/response', [PaypartsController::class, 'response'])
    ->name('payparts.response')
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/payparts/redirect', [PaypartsController::class, 'redirect'])
    ->name('payparts.redirect');

Route::post('/integrations/binotel/call-settings', [BinotelWebhookController::class, 'callSettings'])
    ->name('integrations.binotel.call-settings')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/integrations/binotel/call-completed', [BinotelWebhookController::class, 'callCompleted'])
    ->name('integrations.binotel.call-completed')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/test/liqpay-status/{order}', function () {
    $orderId = request()->route('order');
    $publicKey  = env('LIQPAY_PUBLIC_KEY');
    $privateKey = env('LIQPAY_PRIVATE_KEY');

    $liqpay = new LiqPay($publicKey, $privateKey);

    $res = $liqpay->api('request', [
        'action' => 'status',
        'version' => 3,
        'order_id' => $orderId,
    ]);

    dd($res);
});

Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/checkout/success/{order}/send-email', [CheckoutController::class, 'sendOrderToEmail'])
    ->name('checkout.success.send-email');

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/info', [CartController::class, 'info'])
    ->name('cart.info')
    ->defaults('guest_stateless', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/cart/sidebar', [CartController::class, 'sidebar'])
    ->name('cart.sidebar')
    ->defaults('guest_stateless', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/favorite/{product}', [FavoriteController::class, 'toggle'])->name('favorite.toggle');
Route::get('/favorites', [FavoriteController::class, 'index'])
    ->name('favorites.index')
    ->defaults('guest_stateless', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/favorites/info', [FavoriteController::class, 'info'])
    ->name('favorites.info')
    ->defaults('guest_stateless', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/profile', function () {
        return view(front_view('pages.profile.index'), [
            'user' => auth()->user(),
        ]);
    })->name('profile.index');

    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('profile/addresses', ClientAddressController::class)
        ->parameters(['addresses' => 'address'])
        ->names([
            'index' => 'profile.addresses.index',
            'create' => 'profile.addresses.create',
            'store' => 'profile.addresses.store',
            'edit' => 'profile.addresses.edit',
            'update' => 'profile.addresses.update',
            'destroy' => 'profile.addresses.destroy',
        ]);

    Route::post('profile/addresses/{address}/coords', [ClientAddressController::class, 'updateCoords'])
        ->name('profile.addresses.update-coords');

    Route::get('/profile/bonus', function () {
        return view(front_view('pages.profile.bonuses.index'));
    })->name('profile.bonuses.index');

    Route::get('/profile/orders', function () {
        return view(front_view('pages.profile.orders.index'));
    })->name('profile.orders.index');

    Route::get('/profile/orders/{order}', function () {
        $orderId = request()->route('order');
        $user = auth()->user();
        if (! $user) {
            abort(403, 'User not authenticated');
        }

        $order = \App\Models\Shop\Order::where('id', $orderId)
            ->where('clients_id', $user->id)
            ->first();

        if (! $order) {
            abort(403, 'Order not found or access denied');
        }

        return view(front_view('pages.profile.orders.show'), compact('order'));
    })->name('profile.orders.show');

    Route::post('/profile/orders/{order}/repeat', [\App\Http\Controllers\Front\OrderController::class, 'repeat'])
        ->name('profile.orders.repeat');
});

Route::middleware(['web'])->group(function () {
    Route::redirect('/orders', '/profile/orders', 302)->name('orders.index');
    Route::redirect('/orders/history', '/profile/orders', 302)->name('orders.history');
    Route::redirect('/bonuses', '/profile/bonus', 302)->name('bonuses.index');
    Route::redirect('/addresses', '/profile/addresses', 302)->name('addresses.index');
    Route::redirect('/login', '/auth', 302)->name('login');

    Route::middleware('guest')->group(function () {
        Route::get('/auth', [ClientAuthController::class, 'show'])->name('auth.show');

        Route::get('/auth/redirect/{provider}', [ClientAuthController::class, 'redirect'])
            ->whereIn('provider', ['google', 'facebook', 'apple'])->name('auth.redirect');

        Route::get('/auth/callback/{provider}', [ClientAuthController::class, 'callback'])
            ->whereIn('provider', ['google', 'facebook', 'apple'])->name('auth.callback');

        Route::post('/auth/register', [ClientAuthController::class, 'register'])->name('auth.register');
        Route::post('/auth/login', [ClientAuthController::class, 'login'])->name('auth.login');

        Route::post('/auth/phone-sms/send-code', [ClientAuthController::class, 'loginPhoneSms'])
            ->name('auth.phone-sms.send-code')->middleware('throttle:5,1');
        Route::post('/auth/phone-sms/verify', [ClientAuthController::class, 'verifyPhoneSms'])
            ->name('auth.phone-sms.verify')->middleware('throttle:10,1');

        Route::post('/auth/save-checkout-url', [ClientAuthController::class, 'saveCheckoutUrl'])
            ->name('auth.save-checkout-url');
    });

    Route::post('/auth/password/send-code', [PasswordResetSmsController::class, 'sendCode'])->name('auth.password.sendCode');
    Route::post('/auth/password/verify', [PasswordResetSmsController::class, 'verify'])->name('auth.password.verify');

    Route::post('/auth/register/send-code', [PhoneRegisterController::class, 'sendCode'])
        ->name('auth.register.send-code')->middleware('throttle:5,1');
    Route::post('/auth/register/verify', [PhoneRegisterController::class, 'verify'])
        ->name('auth.register.verify')->middleware('throttle:10,1');

    Route::post('/auth/logout', [ClientAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::get('/auth/logout', [ClientAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout.get');
});

Route::get('/feedbacks', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('/feedbacks', [ReviewController::class, 'store'])->name('reviews.store');

Route::get('/{categorySlug}/{itemSlug}', function () {
    $categorySlug = (string) request()->route('categorySlug');
    $itemSlug = (string) request()->route('itemSlug');

    $blogCategory = BlogCategory::query()->where('slug', $categorySlug)->first();
    if ($blogCategory) {
        return app(BlogController::class)->showInCategory($categorySlug, $itemSlug);
    }

    $category = ProductCategory::query()->where('slug', $categorySlug)->first();
    if (! $category) {
        return response()->view(front_view('404'), [], 404);
    }

    $product = Product::query()
        ->where('slug', $itemSlug)
        ->where('category_id', $category->id)
        ->first();

    if ($product) {
        return app(ProductController::class)->show($categorySlug, $itemSlug);
    }

    return response()->view(front_view('404'), [], 404);
})
    ->where([
        'categorySlug' => '^(?!ru$|en$)[A-Za-z0-9\-_]+$',
        'itemSlug' => '[^/]+',
    ])
    ->name('product.show');

Route::get('/pies', function () {
    return app(CatalogController::class)->show('pies');
})
    ->name('catalog.index')
    ->defaults('page_cache_candidate', true)
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/nas-blagodaryat', function () {
    $page = Pages::query()->where('slug', 'nas-blagodaryat')->first();
    return app(PageController::class)->show($page);
})->name('blagodaryat.index');

Route::get('/{slug}', function () {
    $slug = (string) request()->route('slug');

    $page = Pages::query()->where('slug', $slug)->first();
    if ($page) {
        return app(PageController::class)->show($page);
    }

    $category = ProductCategory::query()->where('slug', $slug)->first();
    if ($category || $slug == 'pies_hits' || $slug == 'pies_news') {
        return app(CatalogController::class)->show($slug);
    }

    $blogCategory = BlogCategory::query()->where('slug', $slug)->first();
    if ($blogCategory) {
        return app(BlogController::class)->index($slug);
    }

    return response()->view(front_view('404'), [], 404);
})->where('slug', '^(?!ru$|en$)[A-Za-z0-9\-_]+$');

Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])
    ->name('product.reviews.store')
    ->middleware('throttle:5,1');

Route::post('/blog/comments', [BlogController::class, 'storeComment'])
    ->name('blog.comments.store')
    ->middleware('throttle:5,1');

Route::fallback(function () {
    return response()->view(front_view('404'), [], 404);
});
};

Route::prefix('{locale}')
    ->where(['locale' => 'ru|en'])
    ->as('localized.')
    ->group($registerFrontRoutes);

Route::group([], $registerFrontRoutes);
