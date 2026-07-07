<?php

namespace App\Contracts;

interface SeoMeta
{
    public function seoTitle(): ?string;

    public function seoDescription(): ?string;

    public function seoCanonicalUrl(): ?string;

    public function seoRobots(): ?string;

    public function seoOgTitle(): ?string;

    public function seoOgDescription(): ?string;

    public function seoOgImage(): ?string;

    public function seoTwitterTitle(): ?string;

    public function seoTwitterDescription(): ?string;

    public function seoTwitterImage(): ?string;

    public function seoFocusKeyword(): ?string;
}
