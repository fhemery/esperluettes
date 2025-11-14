<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\GenreWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi genres', function () {
    describe('getAll', function () {
        it('lists all genres as DTOs', function () {
            makeRefGenre( 'Fantasy', [
                'description' => 'Fantasy desc',
                'order' => 1,
            ]);
            makeRefGenre( 'SciFi', [
                'slug' => 'sci-fi',
                'description' => null,
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllGenres();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(GenreDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefGenre( 'Active', [
                'is_active' => true,
            ]);
            makeRefGenre( 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllGenres();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllGenres(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('slug helpers', function () {
        it('maps genre slugs to ids from DTO list', function () {
            $g1 = makeRefGenre( 'Fantasy', ['slug' => 'fantasy']);
            $g2 = makeRefGenre( 'Sci-Fi', ['slug' => 'sci-fi']);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $ids = $api->getGenreIdsBySlugs(['fantasy', 'sci-fi']);
            expect($ids)->toContain($g1->id, $g2->id);
        });
    });

    describe('get one', function () {
        it('gets genre by id and slug', function () {
            $genreDto = makeRefGenre( 'Fantasy', [
                'description' => 'Fantasy desc',
                'order' => 1,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getGenreById($genreDto->id);
            $bySlug = $api->getGenreBySlug('fantasy');

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($genreDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe('fantasy');
        });
    });

    describe('create', function () {
        it('creates genre as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new GenreWriteDto(
                slug: 'fantasy',
                name: 'Fantasy',
                description: 'Fantasy desc',
                is_active: true,
                order: 1,
            );

            $created = $api->createGenre($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('Fantasy');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('genre');
        });
    });

    describe('update', function () {
        it('updates genre as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $genreDto = makeRefGenre( 'SciFi', [
                'slug' => 'sci-fi',
                'description' => null,
                'order' => 1,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new GenreWriteDto(
                slug: 'science-fiction',
                name: 'Science Fiction',
                description: null,
                is_active: true,
                order: 1,
            );

            $updated = $api->updateGenre($genreDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('Science Fiction');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('genre');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes genre as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $genreDto = makeRefGenre( 'Temp', [
                'description' => null,
                'order' => 1,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteGenre($genreDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('genre');
            expect($event->refId)->toBe($genreDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new GenreWriteDto(
                slug: 'fantasy',
                name: 'Fantasy',
                description: 'Fantasy desc',
                is_active: true,
                order: 1,
            );

            expect(fn() => $api->createGenre($write))->toThrow(AuthorizationException::class);
        });
    });
});
