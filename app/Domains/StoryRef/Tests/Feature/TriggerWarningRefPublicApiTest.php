<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\TriggerWarningDto;
use App\Domains\StoryRef\Public\Contracts\TriggerWarningWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi trigger warnings', function () {
    describe('getAll', function () {
        it('lists all trigger warnings as DTOs', function () {
            makeRefTriggerWarning($this, 'Violence', [
                'description' => 'Violence desc',
                'order' => 1,
            ]);
            makeRefTriggerWarning($this, 'Abuse', [
                'description' => 'Abuse desc',
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllTriggerWarnings();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(TriggerWarningDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefTriggerWarning($this, 'Active', [
                'is_active' => true,
            ]);
            makeRefTriggerWarning($this, 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllTriggerWarnings();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllTriggerWarnings(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('get one', function () {
        it('gets trigger warning by id and slug', function () {
            $twDto = makeRefTriggerWarning($this, 'Violence', [
                'slug' => 'violence',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getTriggerWarningById($twDto->id);
            $bySlug = $api->getTriggerWarningBySlug($twDto->slug);

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($twDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe($twDto->slug);
        });
    });

    describe('create', function () {
        it('creates trigger warning as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new TriggerWarningWriteDto(
                slug: 'violence',
                name: 'Violence',
                description: 'Violence desc',
                is_active: true,
                order: 1,
            );

            $created = $api->createTriggerWarning($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('Violence');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('trigger_warning');
        });
    });

    describe('update', function () {
        it('updates trigger warning as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $twDto = makeRefTriggerWarning($this, 'Violence', [
                'slug' => 'violence',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new TriggerWarningWriteDto(
                slug: 'abuse',
                name: 'Abuse',
                description: 'Abuse desc',
                is_active: true,
                order: 1,
            );

            $updated = $api->updateTriggerWarning($twDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('Abuse');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('trigger_warning');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes trigger warning as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $twDto = makeRefTriggerWarning($this, 'Temp');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteTriggerWarning($twDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('trigger_warning');
            expect($event->refId)->toBe($twDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new TriggerWarningWriteDto(
                slug: 'violence',
                name: 'Violence',
                description: 'Violence desc',
                is_active: true,
                order: 1,
            );

            expect(fn () => $api->createTriggerWarning($write))->toThrow(AuthorizationException::class);
        });
    });
});
