<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\TypeDto;
use App\Domains\StoryRef\Public\Contracts\TypeWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi types', function () {
    describe('getAll', function () {
        it('lists all types as DTOs', function () {
            makeRefType( 'Short Story', [
                'order' => 1,
            ]);
            makeRefType( 'Novel', [
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllTypes();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(TypeDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefType( 'Active', [
                'is_active' => true,
            ]);
            makeRefType( 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllTypes();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllTypes(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('slug helpers', function () {
        it('resolves type id by slug from DTO list', function () {
            $typeDto = makeRefType( 'My Type', [
                'slug' => 'my-type',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $id = $api->getTypeIdBySlug('my-type');
            expect($id)->toBe($typeDto->id);

            $idCase = $api->getTypeIdBySlug('MY-TYPE');
            expect($idCase)->toBe($typeDto->id);

            $idUnknown = $api->getTypeIdBySlug('unknown');
            expect($idUnknown)->toBeNull();
        });
    });

    describe('get one', function () {
        it('gets type by id and slug', function () {
            $typeDto = makeRefType( 'My Type', [
                'slug' => 'my-type',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getTypeById($typeDto->id);
            $bySlug = $api->getTypeBySlug($typeDto->slug);

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($typeDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe($typeDto->slug);
        });
    });

    describe('create', function () {
        it('creates type as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new TypeWriteDto(
                slug: 'short-story',
                name: 'Short Story',
                is_active: true,
                order: 1,
            );

            $created = $api->createType($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('Short Story');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('type');
        });
    });

    describe('update', function () {
        it('updates type as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $typeDto = makeRefType( 'Old', [
                'slug' => 'old',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new TypeWriteDto(
                slug: 'new',
                name: 'New Name',
                is_active: true,
                order: 1,
            );

            $updated = $api->updateType($typeDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('New Name');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('type');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes type as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $typeDto = makeRefType( 'Temp');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteType($typeDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('type');
            expect($event->refId)->toBe($typeDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new TypeWriteDto(
                slug: 'short-story',
                name: 'Short Story',
                is_active: true,
                order: 1,
            );

            expect(fn () => $api->createType($write))->toThrow(AuthorizationException::class);
        });
    });
});
