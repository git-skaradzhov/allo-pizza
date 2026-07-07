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
use App\Support\Seo\SeoBuilder;
use App\Support\Seo\SeoData;
use App\Support\Seo\StructuredDataGenerator;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);

        View::composer('layouts.app', function ($view) {
            $settings = StoreSetting::current();
            $data = $view->getData();

            $seo = $data['seo'] ?? null;
            $seoBuilder = app(SeoBuilder::class);

            if (! $seo instanceof SeoData) {
                $seo = $seoBuilder->fromLegacyViewData($data);
            } else {
                $seo = $seoBuilder->finalize($seo);
            }

            $structuredData = app(StructuredDataGenerator::class)->generate($seo);

            $view->with([
                'storeSetting' => $settings,
                'storeIsOpen' => app(StoreService::class)->isOpen(),
                'cartCount' => app(CartService::class)->itemCount(),
                'seo' => $seo,
                'structuredData' => $structuredData,
            ]);
        });
    }
}
