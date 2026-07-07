@php
    $gaId = $storeSetting->google_analytics_id ?? null;
    $gtmId = $storeSetting->google_tag_manager_id ?? null;
    $googleVerification = $storeSetting->google_site_verification ?? null;
    $bingVerification = $storeSetting->bing_site_verification ?? null;
    $metaPixelId = $storeSetting->meta_pixel_id ?? null;
@endphp

@if ($googleVerification)
    <meta name="google-site-verification" content="{{ $googleVerification }}">
@endif

@if ($bingVerification)
    <meta name="msvalidate.01" content="{{ $bingVerification }}">
@endif

@if ($gtmId)
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l !== 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', @json($gtmId));
    </script>
@endif

@if ($gaId && ! $gtmId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($gaId));
    </script>
@endif

@if ($metaPixelId)
    {{-- Meta Pixel placeholder: configure pixel ID in store settings --}}
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($metaPixelId));
        fbq('track', 'PageView');
    </script>
@endif
