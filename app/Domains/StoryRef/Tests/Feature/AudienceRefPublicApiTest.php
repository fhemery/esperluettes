<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\AudienceWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi audiences', function () {
    describe('getAll', function () {
        it('lists all audiences as DTOs', function () {
            makeRefAudience($this, 'Young Adult', [
                'order' => 1,
            ]);
            makeRefAudience($this, 'Adults', [
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllAudiences();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(AudienceDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefAudience($this, 'Active', [
                'is_active' => true,
            ]);
            makeRefAudience($this, 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllAudiences();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllAudiences(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('get one', function () {
        it('gets audience by id and slug', function () {
            $audienceDto = makeRefAudience($this, 'Young Adult');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getAudienceById($audienceDto->id);
            $bySlug = $api->getAudienceBySlug($audienceDto->slug);

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($audienceDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe($audienceDto->slug);
        });
    });

    describe('create', function () {
        it('creates audience as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new AudienceWriteDto(
                slug: 'young-adult',
                name: 'Young Adult',
                is_active: true,
                order: 1,
            );

            $created = $api->createAudience($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('Young Adult');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('audience');
        });
    });

    describe('update', function () {
        it('updates audience as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $audienceDto = makeRefAudience($this, 'Adults', [
                'slug' => 'adults',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new AudienceWriteDto(
                slug: 'all-audiences',
                name: 'All Audiences',
                is_active: true,
                order: 1,
            );

            $updated = $api->updateAudience($audienceDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('All Audiences');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('audience');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes audience as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $audienceDto = makeRefAudience($this, 'Temp');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteAudience($audienceDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('audience');
            expect($event->refId)->toBe($audienceDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new AudienceWriteDto(
                slug: 'young-adult',
                name: 'Young Adult',
                is_active: true,
                order: 1,
            );

            expect(fn () => $api->createAudience($write))->toThrow(AuthorizationException::class);
        });
    });
});
