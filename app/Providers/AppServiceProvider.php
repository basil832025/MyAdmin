<?php

namespace App\Providers;

use App\Filament\Auth\LoginResponse as FilamentLoginResponse;
use App\Listeners\SyncFavoritesOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Models\Shop\ClientAddress;
use App\Observers\ClientAddressObserver;
use App\Models\Shop\Order;
use App\Models\Callcenter\Order as CallcenterOrder;
use App\Observers\OrderObserver;
use App\Models\Kitchen\KitchenTicket;
use App\Observers\KitchenTicketObserver;
use App\Support\FrontView;
use App\Services\LiqPayService;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, FilamentLoginResponse::class);

     /*   $this->app->singleton(LiqPayService::class, function ($app) {
            $cfg = config('services.liqpay');

            return new LiqPayService(
                $cfg['public_key'],
                $cfg['private_key'],
            );
        });*/
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {


     //   dd(config('filament-spatie-laravel-translatable-plugin.locales'));
        ClientAddress::observe(ClientAddressObserver::class);
        Order::observe(OrderObserver::class);
        CallcenterOrder::observe(OrderObserver::class);
        KitchenTicket::observe(KitchenTicketObserver::class);
        // один аккуратный вызов
        FrontView::register();
        Blade::anonymousComponentPath(resource_path('views/front/' . config('project.theme', '3piroga') . '/components'));
        Event::listen(Login::class, [SyncFavoritesOnLogin::class, 'handle']);

    }
}
