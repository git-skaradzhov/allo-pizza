<?php

return [
    'title_suffix' => env('SEO_TITLE_SUFFIX', '| Allo! Pizza'),

    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Поръчайте пица онлайн с бърза доставка в Русе. Свежи продукти, промо кодове и удобна онлайн поръчка от Allo! Pizza.',
    ),

    'default_image' => 'images/logo-wide.png',

    'locale' => 'bg_BG',

    'twitter_card' => 'summary_large_image',

    'noindex_route_names' => [
        'cart',
        'cart.add',
        'cart.items.update',
        'cart.items.remove',
        'cart.promo.apply',
        'cart.promo.remove',
        'checkout',
        'checkout.store',
        'login',
        'register',
        'password.request',
        'password.reset',
        'password.email',
        'password.store',
        'verification.notice',
        'verification.verify',
        'account.index',
        'account.profile.update',
        'account.password.update',
        'account.password.reset-link',
        'account.orders',
        'account.orders.show',
        'account.orders.reorder',
        'account.addresses',
        'account.addresses.store',
        'account.addresses.update',
        'account.addresses.destroy',
        'lunch.add-selected',
        'lunch.items.add',
    ],

    'sitemap' => [
        'home_priority' => '1.0',
        'menu_priority' => '0.9',
        'category_priority' => '0.8',
        'product_priority' => '0.7',
        'page_priority' => '0.6',
        'lunch_priority' => '0.8',
    ],

    'robots_disallow' => [
        '/admin',
        '/account',
        '/cart',
        '/checkout',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/verify-email',
        '/api/',
        '/livewire/',
        '/filament/',
    ],
];
