<?php

namespace App\Domains\StoryRef\Services;

use Illuminate\Support\Collection;

class StoryRefLookupService
{
    public function __construct(
        private readonly StoryRefCache $cache,
    )
    {
    }

    /**
     * List active story types ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getTypes(): Collection
    {
        // Only expose whitelisted fields for UI
        return $this->cache->types()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'order' => $t['order'],
        ]);
    }

    /**
     * Get all story referentials needed by the UI, cached.
     *
     * Currently includes:
     * - types: Collection of arrays with id, slug, name, order
     *
     * @return array{
     *     types: Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     * }
     */
    public function getStoryReferentials(): array
    {
        return [
            'types' => $this->getTypes(),
        ];
    }

    public function findTypeIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->typeIdBySlug($slug);
    }
}
