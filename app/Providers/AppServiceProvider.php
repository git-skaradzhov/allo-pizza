<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StoreSetting;
use App\Observers\OrderObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
use App\Services\CartService;
use App\Services\StoreService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);

        View::composer('layouts.app', function ($view) {
            $settings = StoreSetting::current();

            $view->with([
                'storeSetting' => $settings,
                'storeIsOpen' => app(StoreService::class)->isOpen(),
                'cartCount' => app(CartService::class)->itemCount(),
            ]);
        });
    }
}
