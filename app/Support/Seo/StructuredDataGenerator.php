<?php

namespace App\Support\Seo;

use App\Models\Product;
use App\Models\StoreSetting;

class StructuredDataGenerator
{
    private readonly StoreSetting $settings;

    public function __construct()
    {
        $this->settings = StoreSetting::current();
    }

    public function generate(SeoData $seo): array
    {
        $graphs = [
            $this->organization(),
            $this->website(),
            $this->webPage($seo),
        ];

        if (! empty($seo->breadcrumbs)) {
            $graphs[] = $this->breadcrumbList($seo);
        }

        if ($seo->pageType === 'product' && isset($seo->context['product']) && $seo->context['product'] instanceof Product) {
            $graphs[] = $this->product($seo->context['product']);
        }

        if ($seo->pageType === 'article' && isset($seo->context['page'])) {
            $graphs[] = $this->article($seo);
        }

        if ($this->isContactPage($seo)) {
            $graphs[] = $this->localBusiness();
        }

        return array_values(array_filter($graphs));
    }

    private function organization(): array
    {
        $logo = $this->settings->organization_logo
            ? absolute_url(public_media_url($this->settings->organization_logo))
            : absolute_url(asset(config('seo.default_image')));

        return [
            '@type' => 'Organization',
            '@id' => url('/#organization'),
            'name' => $this->settings->store_name,
            'url' => url('/'),
            'logo' => $logo,
            'email' => $this->settings->store_email,
            'telephone' => collect($this->settings->phoneNumbers())
                ->map(fn (string $phone) => StoreSetting::normalizePhone($phone))
                ->values()
                ->all(),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->settings->store_address,
                'addressLocality' => 'Русе',
                'addressCountry' => 'BG',
            ],
            'description' => $this->settings->organization_description
                ?: $this->settings->seo_default_description
                ?: config('seo.default_description'),
        ];
    }

    private function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'url' => url('/'),
            'name' => $this->settings->store_name,
            'publisher' => ['@id' => url('/#organization')],
            'inLanguage' => 'bg-BG',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('menu').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    private function webPage(SeoData $seo): array
    {
        return [
            '@type' => 'WebPage',
            '@id' => ($seo->canonical ?: url()->current()).'#webpage',
            'url' => $seo->canonical ?: url()->current(),
            'name' => $seo->title,
            'description' => $seo->description,
            'isPartOf' => ['@id' => url('/#website')],
            'about' => ['@id' => url('/#organization')],
            'inLanguage' => 'bg-BG',
        ];
    }

    private function breadcrumbList(SeoData $seo): array
    {
        $items = [];
        $position = 1;

        foreach ($seo->breadcrumbs as $crumb) {
            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb['label'] ?? null,
                'item' => isset($crumb['url']) ? $crumb['url'] : null,
            ]);
            $position++;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function product(Product $product): array
    {
        $variant = $product->variants->first();
        $price = (float) ($variant->price ?? $product->base_price);
        $images = collect([$product->image])
            ->merge($product->images->pluck('image'))
            ->filter()
            ->map(fn (string $path) => absolute_url(product_image_url($path, product_image_tier_size('large'))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return array_filter([
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->seo_description ?? $product->short_description,
            'sku' => $product->slug,
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->settings->store_name,
            ],
            'image' => $images ?: null,
            'url' => route('product.show', $product->slug),
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product.show', $product->slug),
                'priceCurrency' => 'EUR',
                'price' => number_format($price, 2, '.', ''),
                'availability' => $product->is_active
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ]);
    }

    private function article(SeoData $seo): array
    {
        $page = $seo->context['page'];

        return array_filter([
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $seo->description,
            'url' => $seo->canonical,
            'dateModified' => $page->updated_at?->toAtomString(),
            'author' => [
                '@type' => 'Organization',
                'name' => $this->settings->store_name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->settings->store_name,
            ],
            'inLanguage' => 'bg-BG',
        ]);
    }

    private function localBusiness(): array
    {
        return [
            '@type' => ['Restaurant', 'LocalBusiness'],
            '@id' => url('/pages/kontakti#localbusiness'),
            'name' => $this->settings->store_name,
            'url' => url('/'),
            'image' => $this->settings->organization_logo
                ? absolute_url(public_media_url($this->settings->organization_logo))
                : absolute_url(asset(config('seo.default_image'))),
            'telephone' => collect($this->settings->phoneNumbers())
                ->map(fn (string $phone) => StoreSetting::normalizePhone($phone))
                ->first(),
            'email' => $this->settings->store_email,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->settings->store_address,
                'addressLocality' => 'Русе',
                'addressCountry' => 'BG',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $this->settings->store_lat,
                'longitude' => $this->settings->store_lng,
            ],
            'servesCuisine' => 'Pizza',
            'priceRange' => '€€',
        ];
    }

    private function isContactPage(SeoData $seo): bool
    {
        if (isset($seo->context['page'])) {
            return $seo->context['page']->slug === 'kontakti';
        }

        return str_contains((string) $seo->canonical, '/pages/kontakti');
    }
}
