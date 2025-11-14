<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\StatusDto;
use App\Domains\StoryRef\Public\Contracts\StatusWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi statuses', function () {
    describe('getAll', function () {
        it('lists all statuses as DTOs', function () {
            makeRefStatus( 'Draft', [
                'description' => 'Draft desc',
                'order' => 1,
            ]);
            makeRefStatus( 'Completed', [
                'description' => 'Completed desc',
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllStatuses();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(StatusDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefStatus( 'Active', [
                'is_active' => true,
            ]);
            makeRefStatus( 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllStatuses();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllStatuses(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('get one', function () {
        it('gets status by id and slug', function () {
            $statusDto = makeRefStatus( 'Ongoing', [
                'slug' => 'ongoing',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getStatusById($statusDto->id);
            $bySlug = $api->getStatusBySlug($statusDto->slug);

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($statusDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe($statusDto->slug);
        });
    });

    describe('create', function () {
        it('creates status as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new StatusWriteDto(
                slug: 'ongoing',
                name: 'Ongoing',
                description: 'Ongoing desc',
                is_active: true,
                order: 1,
            );

            $created = $api->createStatus($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('Ongoing');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('status');
        });
    });

    describe('update', function () {
        it('updates status as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $statusDto = makeRefStatus( 'Draft', [
                'slug' => 'draft',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new StatusWriteDto(
                slug: 'completed',
                name: 'Completed',
                description: 'Completed desc',
                is_active: true,
                order: 1,
            );

            $updated = $api->updateStatus($statusDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('Completed');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('status');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes status as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $statusDto = makeRefStatus( 'Temp');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteStatus($statusDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('status');
            expect($event->refId)->toBe($statusDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new StatusWriteDto(
                slug: 'ongoing',
                name: 'Ongoing',
                description: 'Ongoing desc',
                is_active: true,
                order: 1,
            );

            expect(fn () => $api->createStatus($write))->toThrow(AuthorizationException::class);
        });
    });
});
