<?php

namespace App\Concerns;

use App\Contracts\SeoMeta;

trait HasSeoFields
{
    public function seoTitle(): ?string
    {
        return $this->seo_title;
    }

    public function seoDescription(): ?string
    {
        return $this->seo_description;
    }

    public function seoCanonicalUrl(): ?string
    {
        return $this->canonical_url;
    }

    public function seoRobots(): ?string
    {
        return $this->meta_robots;
    }

    public function seoOgTitle(): ?string
    {
        return $this->og_title;
    }

    public function seoOgDescription(): ?string
    {
        return $this->og_description;
    }

    public function seoOgImage(): ?string
    {
        return $this->og_image;
    }

    public function seoTwitterTitle(): ?string
    {
        return $this->twitter_title;
    }

    public function seoTwitterDescription(): ?string
    {
        return $this->twitter_description;
    }

    public function seoTwitterImage(): ?string
    {
        return $this->twitter_image;
    }

    public function seoFocusKeyword(): ?string
    {
        return $this->focus_keyword;
    }
}
