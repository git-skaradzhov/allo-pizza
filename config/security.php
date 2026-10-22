<?php

return [
    /*
    | Full script/style CSP is not enabled: Livewire 3, Alpine and Filament 3
    | require 'unsafe-inline' and 'unsafe-eval', and the storefront also loads
    | Google Maps / GTM / Meta Pixel when configured. frame-ancestors plus the
    | headers below still cover clickjacking, MIME sniffing, referrer leakage
    | and HSTS. A stricter CSP can be added later after an inventory of every
    | admin and storefront script source.
    */
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), payment=(), usb=(), geolocation=(self)',
        'Content-Security-Policy' => "frame-ancestors 'self'",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ],
];
