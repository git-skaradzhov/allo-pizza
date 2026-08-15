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
use App\Services\Meta\MetaConsentService;
use App\Services\Meta\MetaPageEventRegistrar;
use App\Services\Meta\MetaPixelIdResolver;
use App\Services\StoreService;
use App\Support\Seo\SeoBuilder;
use App\Support\Seo\SeoData;
use App\Support\Seo\StructuredDataGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(MetaPageEventRegistrar::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

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

                $registrar = app(MetaPageEventRegistrar::class);
                $registrar->queuePageView(request());

                $shared['metaPageEvents'] = $registrar->browserEvents();
                $shared['metaFlashEvents'] = session('meta_browser_events', []);
                $shared['metaTrackingConfig'] = [
                    'consentUrl' => route('cookie-consent.store'),
                    'eventsUrl' => route('meta.events.store'),
                    'pixelId' => app(MetaPixelIdResolver::class)->resolve($settings),
                    'serverMarketingConsent' => app(MetaConsentService::class)->status(request()),
                ];
            }

            $view->with($shared);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-orders', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('api-auth-login', fn (Request $request) => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

        RateLimiter::for('api-auth-register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('web-auth-login', fn (Request $request) => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

        RateLimiter::for('web-auth-register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('meta-consent', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('meta-events', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
