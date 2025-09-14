<?php

use App\Domains\StoryRef\Services\AudienceService;
use App\Domains\StoryRef\Events\StoryRefAdded;
use App\Domains\StoryRef\Events\StoryRefUpdated;
use App\Domains\StoryRef\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef Audience referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var AudienceService $svc */
        $svc = app(AudienceService::class);
        $ref = $svc->create(['name' => 'Young Adult']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('audience');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Young Adult');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var AudienceService $svc */
        $svc = app(AudienceService::class);
        $ref = $svc->create(['name' => 'Adults']);

        $svc->update($ref->id, ['name' => 'All Audiences']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('audience');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('All Audiences');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var AudienceService $svc */
        $svc = app(AudienceService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('audience');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
