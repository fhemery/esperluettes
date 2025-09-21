<?php

namespace App\Domains\StoryRef\Private\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class StoryRefCache
{
    private const CACHE_TTL_SECONDS = 86400; // 1 day

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly TypeService $typeService,
        private readonly AudienceService $audienceService,
        private readonly CopyrightService $copyrightService,
        private readonly GenreService $genreService,
        private readonly StatusService $statusService,
        private readonly TriggerWarningService $triggerWarningService,
        private readonly FeedbackService $feedbackService,
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

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function audiences(): Collection
    {
        return $this->cache->remember(
            'storyref:audiences:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                $all = collect($this->audienceService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
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

    public function audienceIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->audiences()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    /**
     * @param array<int,string> $slugs
     * @return array<int,int> Audience IDs
     */
    public function audienceIdsBySlugs(array $slugs): array
    {
        $normalized = array_values(array_filter(array_map(fn($s) => trim(strtolower((string) $s)), $slugs)));
        if (empty($normalized)) {
            return [];
        }
        $bySlug = $this->audiences()->keyBy('slug');
        $ids = [];
        foreach ($normalized as $slug) {
            $row = $bySlug->get($slug);
            if (is_array($row) && isset($row['id'])) {
                $ids[] = (int) $row['id'];
            }
        }
        return array_values(array_unique($ids));
    }

    public function clearAudiences(): void
    {
        $this->cache->forget('storyref:audiences:active-ordered');
    }

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function copyrights(): Collection
    {
        return $this->cache->remember(
            'storyref:copyrights:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                $all = collect($this->copyrightService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
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

    public function copyrightIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->copyrights()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    public function clearCopyrights(): void
    {
        $this->cache->forget('storyref:copyrights:active-ordered');
    }

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function genres(): Collection
    {
        return $this->cache->remember(
            'storyref:genres:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                $all = collect($this->genreService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
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

    public function genreIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->genres()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    /**
     * @param array<int,string> $slugs
     * @return array<int,int> Genre IDs
     */
    public function genreIdsBySlugs(array $slugs): array
    {
        $normalized = array_values(array_filter(array_map(fn($s) => trim(strtolower((string) $s)), $slugs)));
        if (empty($normalized)) {
            return [];
        }
        $bySlug = $this->genres()->keyBy('slug');
        $ids = [];
        foreach ($normalized as $slug) {
            $row = $bySlug->get($slug);
            if (is_array($row) && isset($row['id'])) {
                $ids[] = (int) $row['id'];
            }
        }
        return array_values(array_unique($ids));
    }

    public function clearGenres(): void
    {
        $this->cache->forget('storyref:genres:active-ordered');
    }

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function statuses(): Collection
    {
        return $this->cache->remember(
            'storyref:statuses:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                $all = collect($this->statusService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
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

    public function statusIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->statuses()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    public function clearStatuses(): void
    {
        $this->cache->forget('storyref:statuses:active-ordered');
    }

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function feedbacks(): Collection
    {
        return $this->cache->remember(
            'storyref:feedbacks:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                $all = collect($this->feedbackService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
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

    public function feedbackIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->feedbacks()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    public function clearFeedbacks(): void
    {
        $this->cache->forget('storyref:feedbacks:active-ordered');
    }

    /**
     * @return Collection<int, array{id:int,slug:string,name:string,is_active:bool,order:int|null}>
     */
    public function triggerWarnings(): Collection
    {
        return $this->cache->remember(
            'storyref:trigger_warnings:active-ordered',
            self::CACHE_TTL_SECONDS,
            function () {
                $all = collect($this->triggerWarningService->listAll());
                $active = $all->filter(fn($m) => (bool) ($m->is_active ?? true));
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

    public function triggerWarningIdBySlug(string $slug): ?int
    {
        $slug = trim(strtolower($slug));
        if ($slug === '') {
            return null;
        }
        $found = $this->triggerWarnings()->firstWhere('slug', $slug);
        return $found['id'] ?? null;
    }

    /**
     * @param array<int,string> $slugs
     * @return array<int,int> Trigger Warning IDs
     */
    public function triggerWarningIdsBySlugs(array $slugs): array
    {
        $normalized = array_values(array_filter(array_map(fn($s) => trim(strtolower((string) $s)), $slugs)));
        if (empty($normalized)) {
            return [];
        }
        $bySlug = $this->triggerWarnings()->keyBy('slug');
        $ids = [];
        foreach ($normalized as $slug) {
            $row = $bySlug->get($slug);
            if (is_array($row) && isset($row['id'])) {
                $ids[] = (int) $row['id'];
            }
        }
        return array_values(array_unique($ids));
    }

    public function clearTriggerWarnings(): void
    {
        $this->cache->forget('storyref:trigger_warnings:active-ordered');
    }
}
