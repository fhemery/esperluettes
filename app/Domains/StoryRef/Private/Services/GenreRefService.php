<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class GenreRefService
{
    private const CACHE_TTL_SECONDS = 86400; // 1 day

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * @return Collection<int, StoryRefGenre>
     */
    public function getAll(): Collection
    {
        $items = $this->cache->remember(
            'storyref:genres:public:list',
            self::CACHE_TTL_SECONDS,
            function () {
                return StoryRefGenre::query()
                    ->orderBy('order')
                    ->orderBy('name')
                    ->get();
            }
        );

        return $items;
    }

    public function getOneById(int $id): ?StoryRefGenre
    {
        /** @var StoryRefGenre|null $found */
        $found = $this->getAll()->firstWhere('id', $id);

        return $found instanceof StoryRefGenre ? $found : null;
    }

    public function getOneBySlug(string $slug): ?StoryRefGenre
    {
        $normalized = trim(strtolower($slug));
        if ($normalized === '') {
            return null;
        }

        /** @var StoryRefGenre|null $found */
        $found = $this->getAll()->first(function (StoryRefGenre $model) use ($normalized) {
            return strtolower((string) $model->slug) === $normalized;
        });

        return $found instanceof StoryRefGenre ? $found : null;
    }

    /**
     * @param array{name?:string,slug?:string,description?:string|null,is_active?:bool,order?:int|null} $data
     */
    public function create(array $data): StoryRefGenre
    {
        $model = new StoryRefGenre();
        $model->fill($data);
        $model->save();

        $this->clearCache();

        $this->eventBus->emit(new StoryRefAdded(
            refKind: 'genre',
            refId: (int) $model->getKey(),
            refSlug: (string) $model->slug,
            refName: (string) $model->name,
        ));

        return $model;
    }

    /**
     * @param array{name?:string,slug?:string,description?:string|null,is_active?:bool,order?:int|null} $data
     */
    public function update(int $id, array $data): ?StoryRefGenre
    {
        /** @var StoryRefGenre|null $model */
        $model = StoryRefGenre::query()->find($id);
        if (! $model) {
            return null;
        }

        $model->fill($data);

        $changedFields = array_keys($model->getDirty());

        $model->save();

        if (! empty($changedFields)) {
            $this->eventBus->emit(new StoryRefUpdated(
                refKind: 'genre',
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
        /** @var StoryRefGenre|null $model */
        $model = StoryRefGenre::query()->find($id);
        if (! $model) {
            return false;
        }

        $refId = (int) $model->getKey();
        $refSlug = (string) $model->slug;
        $refName = (string) $model->name;

        $deleted = (bool) $model->delete();
        if ($deleted) {
            $this->eventBus->emit(new StoryRefRemoved(
                refKind: 'genre',
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
        $this->cache->forget('storyref:genres:public:list');
    }
}
