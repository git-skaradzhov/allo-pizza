@props([
    'pageEvents' => [],
    'flashEvents' => [],
    'trackingConfig' => null,
])

@php
    use App\Models\StoreSetting;
    use App\Services\Meta\MetaConsentService;
    use App\Services\Meta\MetaPixelIdResolver;

    $settings = $storeSetting ?? StoreSetting::current();
    $gaId = $settings->google_analytics_id ?? null;
    $gtmId = $settings->google_tag_manager_id ?? null;
    $googleVerification = $settings->google_site_verification ?? null;
    $bingVerification = $settings->bing_site_verification ?? null;
    $metaPixelId = app(MetaPixelIdResolver::class)->resolve($settings);
    $hasTracking = $gaId || $gtmId || $metaPixelId;
    $metaTrackingConfig = $trackingConfig ?? [
        'consentUrl' => route('cookie-consent.store'),
        'eventsUrl' => route('meta.events.store'),
        'pixelId' => $metaPixelId,
        'serverMarketingConsent' => app(MetaConsentService::class)->status(request()),
    ];
@endphp

@if ($googleVerification)
    <meta name="google-site-verification" content="{{ $googleVerification }}">
@endif

@if ($bingVerification)
    <meta name="msvalidate.01" content="{{ $bingVerification }}">
@endif

<script type="application/json" id="meta-tracking-config">@json($metaTrackingConfig)</script>
<script type="application/json" id="meta-page-events">@json($pageEvents)</script>
<script type="application/json" id="meta-flash-events">@json($flashEvents)</script>

@if ($hasTracking)
    @php
        $analyticsConfig = [
            'gaId' => $gaId,
            'gtmId' => $gtmId,
            'metaPixelId' => $metaPixelId,
        ];
    @endphp
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            wait_for_update: 500,
        });
    </script>

    <script type="application/json" id="analytics-config">@json($analyticsConfig)</script>
@endif
