<?php

namespace App\Support\Seo;

class SeoData
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public string $robots = 'index,follow',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public ?string $ogUrl = null,
        public string $ogType = 'website',
        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $twitterImage = null,
        public string $twitterCard = 'summary_large_image',
        public ?string $focusKeyword = null,
        public array $breadcrumbs = [],
        public string $pageType = 'webpage',
        public array $context = [],
    ) {}

    public function with(array $attributes): self
    {
        $clone = clone $this;

        foreach ($attributes as $key => $value) {
            if (property_exists($clone, $key)) {
                $clone->{$key} = $value;
            }
        }

        return $clone;
    }
}
