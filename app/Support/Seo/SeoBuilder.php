<?php

namespace App\Support\Seo;

use App\Contracts\SeoMeta;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Support\Str;

class SeoBuilder
{
    private readonly StoreSetting $settings;

    public function __construct()
    {
        $this->settings = StoreSetting::current();
    }

    public function forHome(?Page $homePage = null): SeoData
    {
        $title = $homePage?->seo_title ?: 'Allo! Pizza – Поръчай пица онлайн';
        $description = $homePage?->seo_description ?: config('seo.default_description');

        return $this->make([
            'title' => $this->formatTitle($title),
            'description' => $description,
            'canonical' => route('home'),
            'image' => $this->resolveImage($homePage?->featured_image),
            'pageType' => 'home',
            'breadcrumbs' => [['label' => 'Начало']],
        ]);
    }

    public function forMenu(): SeoData
    {
        return $this->make([
            'title' => $this->formatTitle('Меню'),
            'description' => 'Разгледайте пълното меню с пици, паста, салати и напитки.',
            'canonical' => route('menu'),
            'pageType' => 'menu',
            'breadcrumbs' => [
                ['label' => 'Начало', 'url' => route('home')],
                ['label' => 'Меню'],
            ],
        ]);
    }

    public function forCategory(Category $category): SeoData
    {
        return $this->fromEntity(
            entity: $category,
            defaultTitle: $category->name,
            defaultDescription: $category->description,
            canonical: route('category.show', $category->slug),
            imagePath: $category->image,
            pageType: 'category',
            breadcrumbs: [
                ['label' => 'Начало', 'url' => route('home')],
                ['label' => 'Меню', 'url' => route('menu')],
                ['label' => $category->name],
            ],
            context: ['category' => $category],
        );
    }

    public function forProduct(Product $product): SeoData
    {
        return $this->fromEntity(
            entity: $product,
            defaultTitle: $product->name,
            defaultDescription: $product->seo_description ?? $product->short_description,
            canonical: route('product.show', $product->slug),
            imagePath: $product->image,
            pageType: 'product',
            ogType: 'product',
            breadcrumbs: [
                ['label' => 'Начало', 'url' => route('home')],
                ['label' => $product->category->name, 'url' => route('category.show', $product->category->slug)],
                ['label' => $product->name],
            ],
            context: ['product' => $product],
        );
    }

    public function forPage(Page $page, string $canonicalRoute): SeoData
    {
        return $this->fromEntity(
            entity: $page,
            defaultTitle: $page->title,
            defaultDescription: null,
            canonical: $canonicalRoute,
            imagePath: $page->featured_image,
            pageType: 'article',
            breadcrumbs: [
                ['label' => 'Начало', 'url' => route('home')],
                ['label' => $page->title],
            ],
            context: ['page' => $page],
        );
    }

    public function forLunch(?Page $page): SeoData
    {
        $pageTitle = $page?->title ?? 'Обедно меню';

        return $this->fromEntity(
            entity: $page,
            defaultTitle: $pageTitle,
            defaultDescription: 'Специални обедни предложения с пица, пърленки, салати и напитки на промо цена.',
            canonical: route('lunch.index'),
            imagePath: $page?->featured_image,
            pageType: 'webpage',
            breadcrumbs: [
                ['label' => 'Начало', 'url' => route('home')],
                ['label' => $pageTitle],
            ],
            context: ['page' => $page],
        );
    }

    public function forNewMenu(?Page $page, ?\App\Models\NewMenuHighlight $highlight = null): SeoData
    {
        $pageTitle = $page?->title ?? $highlight?->title ?? 'Ново в менюто';

        return $this->fromEntity(
            entity: $page,
            defaultTitle: $pageTitle,
            defaultDescription: $highlight?->description ?? 'Открийте най-новите предложения в менюто на Allo! Pizza.',
            canonical: route('new-menu.index'),
            imagePath: $page?->featured_image,
            pageType: 'webpage',
            breadcrumbs: [
                ['label' => 'Начало', 'url' => route('home')],
                ['label' => $pageTitle],
            ],
            context: ['page' => $page],
        );
    }

    public function forPrivatePage(string $title, ?string $description = null): SeoData
    {
        return $this->make([
            'title' => $this->formatTitle($title),
            'description' => $description,
            'canonical' => url()->current(),
            'robots' => 'noindex,nofollow',
            'pageType' => 'private',
        ]);
    }

    public function forNotFound(): SeoData
    {
        return $this->make([
            'title' => $this->formatTitle('404'),
            'description' => 'Тази страница се е изплъзнала като парче пица от кутията.',
            'canonical' => url()->current(),
            'robots' => 'noindex,nofollow',
            'pageType' => 'error',
        ]);
    }

    public function fromLegacyViewData(array $data): SeoData
    {
        return $this->make([
            'title' => $this->formatTitle($data['seoTitle'] ?? config('app.name', 'Allo! Pizza')),
            'description' => $data['seoDescription'] ?? null,
            'canonical' => $data['seoCanonical'] ?? url()->current(),
            'image' => $this->resolveImage($data['seoImage'] ?? null),
            'robots' => $data['seoRobots'] ?? $this->defaultRobotsForCurrentRoute(),
            'breadcrumbs' => $data['breadcrumbs'] ?? [],
            'pageType' => $data['seoPageType'] ?? 'webpage',
            'context' => $data['seoContext'] ?? [],
        ]);
    }

    public function finalize(SeoData $seo): SeoData
    {
        $canonical = $seo->canonical ?: url()->current();
        $description = $seo->description ?: $this->settings->seo_default_description ?: config('seo.default_description');
        $image = $seo->ogImage ?: $this->defaultImage();

        return $seo->with([
            'title' => $this->formatTitle($seo->title),
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $seo->robots ?: $this->defaultRobotsForCurrentRoute(),
            'ogTitle' => $seo->ogTitle ?: $seo->title,
            'ogDescription' => $seo->ogDescription ?: $description,
            'ogImage' => $image,
            'ogUrl' => $seo->ogUrl ?: $canonical,
            'twitterTitle' => $seo->twitterTitle ?: $seo->ogTitle ?: $seo->title,
            'twitterDescription' => $seo->twitterDescription ?: $seo->ogDescription ?: $description,
            'twitterImage' => $seo->twitterImage ?: $image,
            'twitterCard' => $seo->twitterCard ?: config('seo.twitter_card', 'summary_large_image'),
        ]);
    }

    private function fromEntity(
        ?SeoMeta $entity,
        string $defaultTitle,
        ?string $defaultDescription,
        string $canonical,
        ?string $imagePath = null,
        string $pageType = 'webpage',
        string $ogType = 'website',
        array $breadcrumbs = [],
        array $context = [],
    ): SeoData {
        $title = $entity?->seoTitle() ?: $defaultTitle;
        $description = $entity?->seoDescription() ?: $defaultDescription;

        return $this->make([
            'title' => $this->formatTitle($title),
            'description' => $description,
            'canonical' => $entity?->seoCanonicalUrl() ?: $canonical,
            'robots' => $entity?->seoRobots(),
            'ogTitle' => $entity?->seoOgTitle(),
            'ogDescription' => $entity?->seoOgDescription(),
            'image' => $this->resolveImage($entity?->seoOgImage() ?: $imagePath),
            'twitterTitle' => $entity?->seoTwitterTitle(),
            'twitterDescription' => $entity?->seoTwitterDescription(),
            'twitterImage' => $this->resolveImage($entity?->seoTwitterImage()),
            'focusKeyword' => $entity?->seoFocusKeyword(),
            'pageType' => $pageType,
            'ogType' => $ogType,
            'breadcrumbs' => $breadcrumbs,
            'context' => $context,
        ]);
    }

    private function make(array $options): SeoData
    {
        $seo = new SeoData(
            title: $options['title'] ?? config('app.name', 'Allo! Pizza'),
            description: $options['description'] ?? null,
            canonical: $options['canonical'] ?? url()->current(),
            robots: $options['robots'] ?? $this->defaultRobotsForCurrentRoute(),
            ogTitle: $options['ogTitle'] ?? null,
            ogDescription: $options['ogDescription'] ?? null,
            ogImage: $options['image'] ?? null,
            ogUrl: $options['ogUrl'] ?? null,
            ogType: $options['ogType'] ?? 'website',
            twitterTitle: $options['twitterTitle'] ?? null,
            twitterDescription: $options['twitterDescription'] ?? null,
            twitterImage: $options['twitterImage'] ?? null,
            twitterCard: $options['twitterCard'] ?? config('seo.twitter_card', 'summary_large_image'),
            focusKeyword: $options['focusKeyword'] ?? null,
            breadcrumbs: $options['breadcrumbs'] ?? [],
            pageType: $options['pageType'] ?? 'webpage',
            context: $options['context'] ?? [],
        );

        return $this->finalize($seo);
    }

    private function formatTitle(string $title): string
    {
        $title = trim($title);
        $suffix = trim((string) ($this->settings->seo_title_suffix ?: config('seo.title_suffix')));

        if ($suffix === '' || Str::endsWith($title, $suffix)) {
            return $title;
        }

        return "{$title} {$suffix}";
    }

    private function defaultImage(): ?string
    {
        $storeImage = $this->settings->seo_default_image;

        if (is_string($storeImage) && $storeImage !== '') {
            return absolute_url(public_media_url($storeImage) ?: $storeImage);
        }

        return absolute_url(asset(config('seo.default_image')));
    }

    private function resolveImage(mixed $image): ?string
    {
        if (! is_string($image) || $image === '') {
            return null;
        }

        if (str_starts_with($image, 'http')) {
            return $image;
        }

        if (str_contains($image, '/') && ! str_starts_with($image, 'images/')) {
            return absolute_url(public_media_url($image) ?: $image);
        }

        if (str_starts_with($image, 'products/')) {
            return absolute_url(product_image_url($image, product_image_tier_size('large')));
        }

        return absolute_url(public_media_url($image) ?: asset($image));
    }

    private function defaultRobotsForCurrentRoute(): string
    {
        $routeName = request()->route()?->getName();

        if ($routeName && in_array($routeName, config('seo.noindex_route_names', []), true)) {
            return 'noindex,nofollow';
        }

        return 'index,follow';
    }
}
