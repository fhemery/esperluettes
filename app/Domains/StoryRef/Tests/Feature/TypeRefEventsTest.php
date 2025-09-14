<?php

use App\Domains\StoryRef\Services\TypeService;
use App\Domains\StoryRef\Events\StoryRefAdded;
use App\Domains\StoryRef\Events\StoryRefUpdated;
use App\Domains\StoryRef\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef Type referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var TypeService $svc */
        $svc = app(TypeService::class);
        $ref = $svc->create(['name' => 'My Type']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('type');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('My Type');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var TypeService $svc */
        $svc = app(TypeService::class);
        $ref = $svc->create(['name' => 'Old']);

        $svc->update($ref->id, ['name' => 'New Name']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('type');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('New Name');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var TypeService $svc */
        $svc = app(TypeService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('type');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
