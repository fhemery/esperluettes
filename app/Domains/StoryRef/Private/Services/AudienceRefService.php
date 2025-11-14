<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class AudienceRefService
{
    private const CACHE_TTL_SECONDS = 86400; // 1 day

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * @return Collection<int, StoryRefAudience>
     */
    public function getAll(): Collection
    {
        $items = $this->cache->remember(
            'storyref:audiences:public:list',
            self::CACHE_TTL_SECONDS,
            function () {
                return StoryRefAudience::query()
                    ->orderBy('order')
                    ->orderBy('name')
                    ->get();
            }
        );

        return $items;
    }

    public function getOneById(int $id): ?StoryRefAudience
    {
        /** @var StoryRefAudience|null $found */
        $found = $this->getAll()->firstWhere('id', $id);

        return $found instanceof StoryRefAudience ? $found : null;
    }

    public function getOneBySlug(string $slug): ?StoryRefAudience
    {
        $normalized = trim(strtolower($slug));
        if ($normalized === '') {
            return null;
        }

        /** @var StoryRefAudience|null $found */
        $found = $this->getAll()->first(function (StoryRefAudience $model) use ($normalized) {
            return strtolower((string) $model->slug) === $normalized;
        });

        return $found instanceof StoryRefAudience ? $found : null;
    }

    /**
     * @param array{name?:string,slug?:string,is_active?:bool,order?:int|null} $data
     */
    public function create(array $data): StoryRefAudience
    {
        $model = new StoryRefAudience();
        $model->fill($data);
        $model->save();

        $this->clearCache();

        $this->eventBus->emit(new StoryRefAdded(
            refKind: 'audience',
            refId: (int) $model->getKey(),
            refSlug: (string) $model->slug,
            refName: (string) $model->name,
        ));

        return $model;
    }

    /**
     * @param array{name?:string,slug?:string,is_active?:bool,order?:int|null} $data
     */
    public function update(int $id, array $data): ?StoryRefAudience
    {
        /** @var StoryRefAudience|null $model */
        $model = StoryRefAudience::query()->find($id);
        if (! $model) {
            return null;
        }

        $model->fill($data);

        $changedFields = array_keys($model->getDirty());

        $model->save();

        if (! empty($changedFields)) {
            $this->eventBus->emit(new StoryRefUpdated(
                refKind: 'audience',
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
        /** @var StoryRefAudience|null $model */
        $model = StoryRefAudience::query()->find($id);
        if (! $model) {
            return false;
        }

        $refId = (int) $model->getKey();
        $refSlug = (string) $model->slug;
        $refName = (string) $model->name;

        $deleted = (bool) $model->delete();
        if ($deleted) {
            $this->eventBus->emit(new StoryRefRemoved(
                refKind: 'audience',
                refId: $refId,
                refSlug: $refSlug,
                refName: $refName,
            ));

            $this->clearCache();
        }

        return $deleted;
    }

    public function clearCache(): void
    {
        $this->cache->forget('storyref:audiences:public:list');
    }
}
