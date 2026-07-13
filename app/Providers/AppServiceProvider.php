<?php

namespace App\Providers;

use App\Models\LunchMenu;
use App\Models\NewMenuHighlight;
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

        View::composer([
            'layouts.app',
            'components.mobile-menu',
            'pages.*',
            'errors.*',
            'auth.*',
            'account.*',
        ], function ($view) {
            $settings = StoreSetting::current();
            $data = $view->getData();
            $storeService = app(StoreService::class);

            $shared = [
                'storeSetting' => $settings,
                'storeIsOpen' => $storeService->isOpen(),
                'isOpen' => $storeService->isOpen(),
                'workingHoursMessage' => $storeService->workingHoursMessage(),
                'storeStatusLabel' => $storeService->storeStatusLabel(),
                'storeStatusDetail' => $storeService->storeStatusDetail(),
                'weeklyWorkingHoursSummary' => $storeService->weeklyScheduleSummary(),
                'cartCount' => app(CartService::class)->itemCount(),
                'showLunchMenuNav' => LunchMenu::isPublished(),
                'showNewMenuNav' => NewMenuHighlight::isPublished(),
            ];

            if ($view->name() === 'layouts.app') {
                $seo = $data['seo'] ?? null;
                $seoBuilder = app(SeoBuilder::class);

                if (! $seo instanceof SeoData) {
                    $seo = $seoBuilder->fromLegacyViewData($data);
                } else {
                    $seo = $seoBuilder->finalize($seo);
                }

                $shared['seo'] = $seo;
                $shared['structuredData'] = app(StructuredDataGenerator::class)->generate($seo);
            }

            $view->with($shared);
        });
    }
}
