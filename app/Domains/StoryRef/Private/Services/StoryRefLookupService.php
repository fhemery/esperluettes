<?php

namespace App\Domains\StoryRef\Private\Services;

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
     * - statuses: Collection of arrays with id, slug, name, order
     * - trigger_warnings: Collection of arrays with id, slug, name, order
     * - feedbacks: Collection of arrays with id, slug, name, order
     *
     * @return array{
     *     types: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     audiences: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     copyrights: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     genres: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     statuses: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     trigger_warnings: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     *     feedbacks: Collection<int, array{id:int,slug:string,name:string,order:int|null}>,
     * }
     */
    public function getStoryReferentials(): array
    {
        return [
            'types' => $this->getTypes(),
            'audiences' => $this->getAudiences(),
            'copyrights' => $this->getCopyrights(),
            'genres' => $this->getGenres(),
            'statuses' => $this->getStatuses(),
            'trigger_warnings' => $this->getTriggerWarnings(),
            'feedbacks' => $this->getFeedbacks(),
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
            'description' => $t['description'],
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
            'description' => $t['description'],
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
            'description' => $t['description'],
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

    /**
     * List active statuses ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getStatuses(): Collection
    {
        return $this->cache->statuses()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'description' => $t['description'],
            'order' => $t['order'],
        ]);
    }

    public function findStatusIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->statusIdBySlug($slug);
    }

    /**
     * List active trigger warnings ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getTriggerWarnings(): Collection
    {
        return $this->cache->triggerWarnings()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'description' => $t['description'],
            'order' => $t['order'],
        ]);
    }

    public function findTriggerWarningIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->triggerWarningIdBySlug($slug);
    }

    /**
     * @param array<int,string>|null $slugs
     * @return array<int,int>
     */
    public function findTriggerWarningIdsBySlugs(?array $slugs): array
    {
        if ($slugs === null) {
            return [];
        }
        return $this->cache->triggerWarningIdsBySlugs($slugs);
    }

    /**
     * List active feedbacks ordered for UI.
     * @return Collection<int, array{id:int,slug:string,name:string,order:int|null}>
     */
    public function getFeedbacks(): Collection
    {
        return $this->cache->feedbacks()->map(fn(array $t) => [
            'id' => $t['id'],
            'slug' => $t['slug'],
            'name' => $t['name'],
            'description' => $t['description'],
            'order' => $t['order'],
        ]);
    }

    public function findFeedbackIdBySlug(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }
        return $this->cache->feedbackIdBySlug($slug);
    }
	
	public function clearCache(): void
	{
		$this->cache->clearAll();
	}
}
