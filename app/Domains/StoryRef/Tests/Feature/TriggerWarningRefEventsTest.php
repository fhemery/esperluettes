<?php

use App\Domains\StoryRef\Private\Services\TriggerWarningService;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRef TriggerWarning referential events', function () {
    it('emits StoryRef.Added on create', function () {
        /** @var TriggerWarningService $svc */
        $svc = app(TriggerWarningService::class);
        $ref = $svc->create(['name' => 'Violence']);

        $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('trigger_warning');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Violence');
    });

    it('emits StoryRef.Updated on update with changed fields', function () {
        /** @var TriggerWarningService $svc */
        $svc = app(TriggerWarningService::class);
        $ref = $svc->create(['name' => 'TW1']);

        $svc->update($ref->id, ['name' => 'TW-Updated']);

        $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('trigger_warning');
        expect($event->refId)->toBe($ref->id);
        expect($event->changedFields)->toContain('name');
        expect($event->refName)->toBe('TW-Updated');
    });

    it('emits StoryRef.Removed on delete', function () {
        /** @var TriggerWarningService $svc */
        $svc = app(TriggerWarningService::class);
        $ref = $svc->create(['name' => 'Temp']);

        $svc->delete($ref->id);

        $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
        expect($event)->not->toBeNull();
        expect($event->refKind)->toBe('trigger_warning');
        expect($event->refId)->toBe($ref->id);
        expect($event->refSlug)->toBe($ref->slug);
        expect($event->refName)->toBe('Temp');
    });
});
