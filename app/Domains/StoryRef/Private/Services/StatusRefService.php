<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class StatusRefService
{
    private const CACHE_TTL_SECONDS = 86400; // 1 day

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * @return Collection<int, StoryRefStatus>
     */
    public function getAll(): Collection
    {
        $items = $this->cache->remember(
            'storyref:statuses:public:list',
            self::CACHE_TTL_SECONDS,
            function () {
                return StoryRefStatus::query()
                    ->orderBy('order')
                    ->orderBy('name')
                    ->get();
            }
        );

        return $items;
    }

    public function getOneById(int $id): ?StoryRefStatus
    {
        /** @var StoryRefStatus|null $found */
        $found = $this->getAll()->firstWhere('id', $id);

        return $found instanceof StoryRefStatus ? $found : null;
    }

    public function getOneBySlug(string $slug): ?StoryRefStatus
    {
        $normalized = trim(strtolower($slug));
        if ($normalized === '') {
            return null;
        }

        /** @var StoryRefStatus|null $found */
        $found = $this->getAll()->first(function (StoryRefStatus $model) use ($normalized) {
            return strtolower((string) $model->slug) === $normalized;
        });

        return $found instanceof StoryRefStatus ? $found : null;
    }

    /**
     * @param array{name?:string,slug?:string,description?:string|null,is_active?:bool,order?:int|null} $data
     */
    public function create(array $data): StoryRefStatus
    {
        $model = new StoryRefStatus();
        $model->fill($data);
        $model->save();

        $this->clearCache();

        $this->eventBus->emit(new StoryRefAdded(
            refKind: 'status',
            refId: (int) $model->getKey(),
            refSlug: (string) $model->slug,
            refName: (string) $model->name,
        ));

        return $model;
    }

    /**
     * @param array{name?:string,slug?:string,description?:string|null,is_active?:bool,order?:int|null} $data
     */
    public function update(int $id, array $data): ?StoryRefStatus
    {
        /** @var StoryRefStatus|null $model */
        $model = StoryRefStatus::query()->find($id);
        if (! $model) {
            return null;
        }

        $model->fill($data);

        $changedFields = array_keys($model->getDirty());

        $model->save();

        if (! empty($changedFields)) {
            $this->eventBus->emit(new StoryRefUpdated(
                refKind: 'status',
                refId: (int) $model->getKey(),
                refSlug: (string) $model->slug,
                refName: (string) $model->name,
                changedFields: $changedFields,
            ));
        }

        $this->clearCache();

        return $model;
    }

    public function delete(int $id): bool
    {
        /** @var StoryRefStatus|null $model */
        $model = StoryRefStatus::query()->find($id);
        if (! $model) {
            return false;
        }

        $refId = (int) $model->getKey();
        $refSlug = (string) $model->slug;
        $refName = (string) $model->name;

        $deleted = (bool) $model->delete();
        if ($deleted) {
            $this->eventBus->emit(new StoryRefRemoved(
                refKind: 'status',
                refId: $refId,
                refSlug: $refSlug,
                refName: $refName,
            ));

            $this->clearCache();
        }

        return $deleted;
    }

    private function clearCache(): void
    {
        $this->cache->forget('storyref:statuses:public:list');
    }
}
