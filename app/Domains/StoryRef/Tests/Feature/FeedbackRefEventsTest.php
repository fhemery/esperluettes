<?php

use App\Domains\StoryRef\Services\FeedbackService;
use App\Domains\StoryRef\Events\StoryRefAdded;
use App\Domains\StoryRef\Events\StoryRefUpdated;
use App\Domains\StoryRef\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef Feedback referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var FeedbackService $svc */
        $svc = app(FeedbackService::class);
        $ref = $svc->create(['name' => 'Critique']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('feedback');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Critique');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var FeedbackService $svc */
        $svc = app(FeedbackService::class);
        $ref = $svc->create(['name' => 'Beta']);

        $svc->update($ref->id, ['name' => 'Alpha']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('feedback');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('Alpha');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var FeedbackService $svc */
        $svc = app(FeedbackService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('feedback');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
