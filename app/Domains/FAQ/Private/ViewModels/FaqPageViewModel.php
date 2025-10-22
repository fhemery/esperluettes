<?php

namespace App\Domains\FAQ\Private\ViewModels;

/**
 * View model for the FAQ page.
 */
class FaqPageViewModel
{
    /** @var FaqTabViewModel[] */
    private array $tabs;
    private ?string $title;
    private ?string $metaDescription;

    public function __construct(
        array $tabs,
        private readonly ?string $initialTabKey,
        ?string $title = null,
        ?string $metaDescription = null,
    ) {
        /** @var FaqTabViewModel[] $tabs */
        $this->tabs = $tabs;
        $this->title = $title;
        $this->metaDescription = $metaDescription;
    }

    /**
     * @return FaqTabViewModel[]
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function getInitialTabKey(): ?string
    {
        return $this->initialTabKey;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    /**
     * Helper to expose an array for Blade components expecting arrays.
     *
     * @return array<int, array{key:string,label:string}>
     */
    public function tabsAsArray(): array
    {
        return array_map(fn(FaqTabViewModel $t) => $t->toArray(), $this->tabs);
    }
}
