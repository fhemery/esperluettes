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
     * - audiences: Collection of arrays with id, slug, name, order
     * - copyrights: Collection of arrays with id, slug, name, order
     * - genres: Collection of arrays with id, slug, name, order
     *
     * @return array{
     *     types: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     audiences: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     copyrights: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     genres: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     * }
     */
    public function getStoryReferentials(): array
    {
        return [
            'types' => $this->getTypes(),
            'audiences' => $this->getAudiences(),
            'copyrights' => $this->getCopyrights(),
            'genres' => $this->getGenres(),
        ];
    }

    public function findTypeIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->typeIdBySlug($slug);
    }

    /**
     * List active audiences ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getAudiences(): Collection
    {
        return $this->cache->audiences()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'order' => $t['order'],
        ]);
    }

    public function findAudienceIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->audienceIdBySlug($slug);
    }

    /**
     * @param array<int,string>|null $slugs
     * @return array<int,int>
     */
    public function findAudienceIdsBySlugs(?array $slugs): array
    {
        if ($slugs === null) {
            return [];
        }
        return $this->cache->audienceIdsBySlugs($slugs);
    }

    /**
     * List active copyrights ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getCopyrights(): Collection
    {
        return $this->cache->copyrights()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'order' => $t['order'],
        ]);
    }

    /**
     * List active genres ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getGenres(): Collection
    {
        return $this->cache->genres()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'order' => $t['order'],
        ]);
    }

    public function findGenreIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->genreIdBySlug($slug);
    }

    /**
     * @param array<int,string>|null $slugs
     * @return array<int,int>
     */
    public function findGenreIdsBySlugs(?array $slugs): array
    {
        if ($slugs === null) {
            return [];
        }
        return $this->cache->genreIdsBySlugs($slugs);
    }
}
