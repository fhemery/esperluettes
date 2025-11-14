<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\CopyrightDto;
use App\Domains\StoryRef\Public\Contracts\CopyrightWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi copyrights', function () {
    describe('getAll', function () {
        it('lists all copyrights as DTOs', function () {
            makeRefCopyright( 'CC BY', [
                'description' => 'CC BY desc',
                'order' => 1,
            ]);
            makeRefCopyright( 'CC BY-SA', [
                'description' => 'CC BY-SA desc',
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllCopyrights();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(CopyrightDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefCopyright( 'Active', [
                'is_active' => true,
            ]);
            makeRefCopyright( 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllCopyrights();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllCopyrights(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('get one', function () {
        it('gets copyright by id and slug', function () {
            $copyrightDto = makeRefCopyright( 'CC BY', [
                'slug' => 'cc-by',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getCopyrightById($copyrightDto->id);
            $bySlug = $api->getCopyrightBySlug($copyrightDto->slug);

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($copyrightDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe($copyrightDto->slug);
        });
    });

    describe('create', function () {
        it('creates copyright as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new CopyrightWriteDto(
                slug: 'cc-by',
                name: 'CC BY',
                description: 'CC BY desc',
                is_active: true,
                order: 1,
            );

            $created = $api->createCopyright($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('CC BY');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('copyright');
        });
    });

    describe('update', function () {
        it('updates copyright as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $copyrightDto = makeRefCopyright( 'License A', [
                'slug' => 'license-a',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new CopyrightWriteDto(
                slug: 'license-b',
                name: 'License B',
                description: 'License B desc',
                is_active: true,
                order: 1,
            );

            $updated = $api->updateCopyright($copyrightDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('License B');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('copyright');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes copyright as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $copyrightDto = makeRefCopyright( 'Temp');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteCopyright($copyrightDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('copyright');
            expect($event->refId)->toBe($copyrightDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new CopyrightWriteDto(
                slug: 'cc-by',
                name: 'CC BY',
                description: 'CC BY desc',
                is_active: true,
                order: 1,
            );

            expect(fn () => $api->createCopyright($write))->toThrow(AuthorizationException::class);
        });
    });
});
