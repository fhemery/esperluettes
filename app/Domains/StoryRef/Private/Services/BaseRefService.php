<?php

namespace App\Domains\StoryRef\Private\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;

abstract class BaseRefService
{
    /** @var class-string<Model> */
    protected string $modelClass;

    /** Whether the table has an 'order' column */
    protected bool $hasOrder = false;

    /** Whether the table has a 'description' column (only used for fillable convenience) */
    protected bool $hasDescription = false;

    /**
     * Logical ref kind used in events (e.g., type, genre, audience, status, trigger_warning, feedback, copyright)
     */
    protected string $refKind = 'ref';

    public function __construct(
        private readonly EventBus $eventBus,
    ) {
        // Children must set $modelClass, $refKind and flags in their constructor or property defaults
    }

    protected function newQuery()
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        return $model->newQuery();
    }

    public function listAll(?string $orderBy = null, string $direction = 'asc')
    {
        $query = $this->newQuery();
        if ($orderBy) {
            $query->orderBy($orderBy, $direction);
        } elseif ($this->hasOrder) {
            $query->orderBy('order');
        } else {
            $query->orderBy('name');
        }
        return $query->get();
    }

    public function findById(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    public function create(array $data): Model
    {
        $payload = $this->preparePayload($data, isUpdate: false);
        /** @var Model $created */
        $created = $this->newQuery()->create($payload);
        // Emit added event
        $this->eventBus->emit(new StoryRefAdded(
            refKind: $this->refKind,
            refId: (int) $created->getKey(),
            refSlug: (string) ($created->slug ?? ''),
            refName: (string) ($created->name ?? ''),
        ));
        return $created;
    }

    public function update(int $id, array $data): ?Model
    {
        $model = $this->findById($id);
        if (!$model) {
            return null;
        }
        $payload = $this->preparePayload($data, isUpdate: true, current: $model);
        $model->fill($payload);
        // Capture changed fields before saving
        $changedFields = array_keys($model->getDirty());
        $model->save();
        if (!empty($changedFields)) {
            $this->eventBus->emit(new StoryRefUpdated(
                refKind: $this->refKind,
                refId: (int) $model->getKey(),
                refSlug: (string) ($model->slug ?? ''),
                refName: (string) ($model->name ?? ''),
                changedFields: $changedFields,
            ));
        }
        return $model;
    }

    public function delete(int $id): bool
    {
        $model = $this->findById($id);
        if (!$model) {
            return false;
        }
        $refId = (int) $model->getKey();
        $refSlug = (string) ($model->slug ?? '');
        $refName = (string) ($model->name ?? '');
        $deleted = (bool) $model->delete();
        if ($deleted) {
            $this->eventBus->emit(new StoryRefRemoved(
                refKind: $this->refKind,
                refId: $refId,
                refSlug: $refSlug,
                refName: $refName,
            ));
        }
        return $deleted;
    }

    protected function preparePayload(array $data, bool $isUpdate = false, ?Model $current = null): array
    {
        $payload = [];

        // name
        if (array_key_exists('name', $data)) {
            $payload['name'] = (string) $data['name'];
        }

        // description (optional)
        if ($this->hasDescription && array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        // is_active
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        // slug: unique, auto from name if missing
        $slug = $data['slug'] ?? null;
        if (!$slug && isset($payload['name'])) {
            $slug = Str::slug($payload['name']);
        }
        if ($slug) {
            $slug = $this->makeUniqueSlug($slug, $current?->getKey());
            $payload['slug'] = $slug;
        }

        // order: compute only when creating and only for tables that have it
        if ($this->hasOrder && !$isUpdate) {
            $payload['order'] = $this->computeNextOrder();
        }

        return $payload;
    }

    protected function computeNextOrder(): int
    {
        $max = (int) $this->newQuery()->max('order');
        return $max + 1;
    }

    protected function makeUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = Str::slug($baseSlug);
        $i = 0;
        do {
            $candidate = $i === 0 ? $slug : $slug.'-'.$i;
            $query = $this->newQuery()->where('slug', $candidate);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $exists = $query->exists();
            $i++;
        } while ($exists);

        return $candidate;
    }
}
