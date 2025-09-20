<?php

use App\Domains\StoryRef\Private\Services\CopyrightService;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef Copyright referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var CopyrightService $svc */
        $svc = app(CopyrightService::class);
        $ref = $svc->create(['name' => 'CC BY']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('copyright');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('CC BY');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var CopyrightService $svc */
        $svc = app(CopyrightService::class);
        $ref = $svc->create(['name' => 'License A']);

        $svc->update($ref->id, ['name' => 'License B']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('copyright');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('License B');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var CopyrightService $svc */
        $svc = app(CopyrightService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('copyright');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
