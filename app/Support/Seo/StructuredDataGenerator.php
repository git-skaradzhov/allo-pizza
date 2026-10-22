<?php

namespace App\Support\Seo;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\StoreService;
use Illuminate\Support\Collection;

class StructuredDataGenerator
{
    private readonly StoreSetting $settings;

    public function __construct(
        private StoreService $storeService,
    ) {
        $this->settings = StoreSetting::current();
    }

    public function generate(SeoData $seo): array
    {
        $graphs = [
            $this->organization(),
            $this->restaurant(),
            $this->website(),
            $this->webPage($seo),
        ];

        if (! empty($seo->breadcrumbs)) {
            $graphs[] = $this->breadcrumbList($seo);
        }

        if ($seo->pageType === 'product' && isset($seo->context['product']) && $seo->context['product'] instanceof Product) {
            $graphs[] = $this->product($seo->context['product']);
        }

        if ($seo->pageType === 'menu') {
            $graphs[] = $this->menu($seo);
        }

        if ($seo->pageType === 'article' && isset($seo->context['page'])) {
            $graphs[] = $this->article($seo);
        }

        return array_values(array_filter($graphs));
    }

    private function organization(): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => url('/#organization'),
            'name' => $this->settings->store_name,
            'url' => url('/'),
            'logo' => $this->logoUrl(),
            'email' => $this->settings->store_email,
            'telephone' => $this->telephoneList(),
            'address' => $this->postalAddress(),
            'description' => $this->settings->organization_description
                ?: $this->settings->seo_default_description
                ?: config('seo.default_description'),
        ]);
    }

    private function restaurant(): array
    {
        return array_filter([
            '@type' => ['Restaurant', 'LocalBusiness'],
            '@id' => url('/#restaurant'),
            'name' => $this->settings->store_name,
            'url' => url('/'),
            'image' => $this->logoUrl(),
            'telephone' => $this->telephoneList()[0] ?? null,
            'email' => $this->settings->store_email,
            'address' => $this->postalAddress(),
            'geo' => $this->geo(),
            'openingHours' => $this->storeService->schemaOpeningHours(),
            'openingHoursSpecification' => $this->storeService->schemaOpeningHoursSpecification(),
            'servesCuisine' => config('seo.serves_cuisine', 'Pizza'),
            'priceRange' => config('seo.price_range', '€€'),
            'hasMenu' => [
                '@type' => 'Menu',
                '@id' => route('menu').'#menu',
                'url' => route('menu'),
            ],
            'parentOrganization' => ['@id' => url('/#organization')],
        ]);
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
            'about' => ['@id' => url('/#restaurant')],
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
            'offers' => $this->offer($price, route('product.show', $product->slug), (bool) $product->is_active),
        ]);
    }

    private function menu(SeoData $seo): array
    {
        $categories = $seo->context['categories'] ?? [];

        if ($categories instanceof Collection) {
            $categoryItems = $categories;
        } else {
            $categoryItems = collect($categories);
        }

        $sections = $categoryItems
            ->filter(fn ($category) => $category instanceof Category)
            ->map(function (Category $category) {
                $items = collect($category->products ?? [])
                    ->filter(fn ($product) => $product instanceof Product)
                    ->map(fn (Product $product) => $this->menuItem($product))
                    ->values()
                    ->all();

                return array_filter([
                    '@type' => 'MenuSection',
                    'name' => $category->name,
                    'hasMenuItem' => $items ?: null,
                ]);
            })
            ->values()
            ->all();

        return array_filter([
            '@type' => 'Menu',
            '@id' => route('menu').'#menu',
            'name' => 'Меню',
            'url' => route('menu'),
            'hasMenuSection' => $sections ?: null,
        ]);
    }

    private function menuItem(Product $product): array
    {
        $variant = $product->variants->first();
        $price = (float) ($variant->price ?? $product->base_price);

        return array_filter([
            '@type' => 'MenuItem',
            'name' => $product->name,
            'description' => $product->short_description,
            'url' => route('product.show', $product->slug),
            'offers' => $this->offer($price, route('product.show', $product->slug), (bool) $product->is_active),
        ]);
    }

    private function offer(float $price, string $url, bool $inStock): array
    {
        return [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'EUR',
            'price' => number_format($price, 2, '.', ''),
            'availability' => $inStock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ];
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

    private function logoUrl(): ?string
    {
        if ($this->settings->organization_logo) {
            return absolute_url(public_media_url($this->settings->organization_logo));
        }

        return absolute_url(asset(config('seo.logo')));
    }

    /**
     * @return list<string>
     */
    private function telephoneList(): array
    {
        return collect($this->settings->phoneNumbers())
            ->map(fn (string $phone) => StoreSetting::normalizePhone($phone))
            ->values()
            ->all();
    }

    private function postalAddress(): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $this->settings->streetAddress() ?: $this->settings->store_address,
            'addressLocality' => $this->settings->city(),
            'addressCountry' => 'BG',
        ]);
    }

    private function geo(): ?array
    {
        if ($this->settings->store_lat === null || $this->settings->store_lng === null) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => $this->settings->store_lat,
            'longitude' => $this->settings->store_lng,
        ];
    }
}
