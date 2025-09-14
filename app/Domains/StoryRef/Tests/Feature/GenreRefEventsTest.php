<?php

use App\Domains\StoryRef\Services\GenreService;
use App\Domains\StoryRef\Events\StoryRefAdded;
use App\Domains\StoryRef\Events\StoryRefUpdated;
use App\Domains\StoryRef\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef Genre referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var GenreService $svc */
        $svc = app(GenreService::class);
        $ref = $svc->create(['name' => 'Fantasy']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('genre');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Fantasy');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var GenreService $svc */
        $svc = app(GenreService::class);
        $ref = $svc->create(['name' => 'SciFi']);

        $svc->update($ref->id, ['name' => 'Science Fiction']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('genre');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('Science Fiction');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var GenreService $svc */
        $svc = app(GenreService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('genre');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
