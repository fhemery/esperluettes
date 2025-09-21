<?php

use App\Domains\StoryRef\Private\Services\StatusService;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef Status referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var StatusService $svc */
        $svc = app(StatusService::class);
        $ref = $svc->create(['name' => 'Ongoing']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('status');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Ongoing');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var StatusService $svc */
        $svc = app(StatusService::class);
        $ref = $svc->create(['name' => 'Draft']);

        $svc->update($ref->id, ['name' => 'Completed']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('status');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('Completed');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var StatusService $svc */
        $svc = app(StatusService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('status');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
