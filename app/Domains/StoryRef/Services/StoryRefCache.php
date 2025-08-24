<?php

namespace App\Domains\StoryRef\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class StoryRefCache
{
    private const CACHE_TTL_SECONDS = 86400; // 1 day

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly TypeService $typeService,
    ) {}

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function types(): Collection
    {
        return $this->cache->remember(
            'storyref:types:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                // Use service to list all, then filter active and sort by order/name
                $all = collect($this->typeService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
                // Ensure stable ordering
                return $active
                    ->sortBy([
                        ['order', 'asc'],
                        ['name', 'asc'],
                    ])
                    ->values()
                    ->map(fn($m) => [
                        'id' => (int) $m->id,
                        'slug' => (string) $m->slug,
                        'name' => (string) $m->name,
                        'is_active' => (bool) ($m->is_active ?? true),
                        'order' => isset($m->order) ? (int) $m->order : null,
                    ]);
            }
        );
    }

    public function typeIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->types()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    // Future: add clear methods triggered by admin updates
    public function clearTypes(): void
    {
        $this->cache->forget('storyref:types:active-ordered');
    }
}
