<?php

namespace App\Domains\Shared\ViewModels;

class PageViewModel
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?SeoViewModel $seo = null,
        public readonly ?BreadcrumbViewModel $breadcrumbs = null,
        public readonly bool $seasonalBackground = false,
        public readonly bool $seasonalRibbon = false
    ) {}

    public static function make(): self
    {
        return new self();
    }

    public function withTitle(?string $title): self
    {
        return new self($title, $this->seo, $this->breadcrumbs, $this->seasonalBackground);
    }

    public function withSeo(?SeoViewModel $seo): self
    {
        return new self($this->title, $seo, $this->breadcrumbs, $this->seasonalBackground);
    }

    public function withBreadcrumbs(?BreadcrumbViewModel $breadcrumbs): self
    {
        return new self($this->title, $this->seo, $breadcrumbs, $this->seasonalBackground);
    }

    public function withSeasonalBackground(?bool $seasonal): self
    {
        return new self($this->title, $this->seo, $this->breadcrumbs, $seasonal);
    }

    public function withSeasonalRibbon(?bool $seasonal): self
    {
        return new self($this->title, $this->seo, $this->breadcrumbs, $this->seasonalBackground, $seasonal);
    }
}
