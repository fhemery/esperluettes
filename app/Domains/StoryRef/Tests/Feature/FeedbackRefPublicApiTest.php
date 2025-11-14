<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\FeedbackDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi feedbacks', function () {
    describe('getAll', function () {
        it('lists all feedbacks as DTOs', function () {
            makeRefFeedback($this, 'Comments', [
                'description' => 'Comments desc',
                'order' => 1,
            ]);
            makeRefFeedback($this, 'Reviews', [
                'description' => 'Reviews desc',
                'order' => 2,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $dtos = $api->getAllFeedbacks();

            expect($dtos)->toHaveCount(2);
            expect($dtos->first())->toBeInstanceOf(FeedbackDto::class);
        });

        it('filters by activeOnly flag in filter dto', function () {
            makeRefFeedback($this, 'Active', [
                'is_active' => true,
            ]);
            makeRefFeedback($this, 'Inactive', [
                'is_active' => false,
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $activeOnly = $api->getAllFeedbacks();
            expect($activeOnly)->toHaveCount(1);
            expect($activeOnly->first()->name)->toBe('Active');

            $all = $api->getAllFeedbacks(new StoryRefFilterDto(activeOnly: false));
            expect($all)->toHaveCount(2);
        });
    });

    describe('get one', function () {
        it('gets feedback by id and slug', function () {
            $feedbackDto = makeRefFeedback($this, 'Comments', [
                'slug' => 'comments',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $byId = $api->getFeedbackById($feedbackDto->id);
            $bySlug = $api->getFeedbackBySlug($feedbackDto->slug);

            expect($byId)->not->toBeNull();
            expect($byId->id)->toBe($feedbackDto->id);
            expect($bySlug)->not->toBeNull();
            expect($bySlug->slug)->toBe($feedbackDto->slug);
        });
    });

    describe('create', function () {
        it('creates feedback as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new FeedbackWriteDto(
                slug: 'comments',
                name: 'Comments',
                description: 'Comments desc',
                is_active: true,
                order: 1,
            );

            $created = $api->createFeedback($write);

            expect($created->id)->toBeInt();
            expect($created->name)->toBe('Comments');

            $event = latestEventOf(StoryRefAdded::name(), StoryRefAdded::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('feedback');
        });
    });

    describe('update', function () {
        it('updates feedback as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $feedbackDto = makeRefFeedback($this, 'Comments', [
                'slug' => 'comments',
            ]);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new FeedbackWriteDto(
                slug: 'reviews',
                name: 'Reviews',
                description: 'Reviews desc',
                is_active: true,
                order: 1,
            );

            $updated = $api->updateFeedback($feedbackDto->id, $write);

            expect($updated)->not->toBeNull();
            expect($updated->name)->toBe('Reviews');

            $event = latestEventOf(StoryRefUpdated::name(), StoryRefUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('feedback');
            expect($event->changedFields)->toContain('name');
        });
    });

    describe('delete', function () {
        it('deletes feedback as admin and emits event', function () {
            $user = admin($this);
            $this->actingAs($user);

            $feedbackDto = makeRefFeedback($this, 'Temp');

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $deleted = $api->deleteFeedback($feedbackDto->id);

            expect($deleted)->toBeTrue();

            $event = latestEventOf(StoryRefRemoved::name(), StoryRefRemoved::class);
            expect($event)->not->toBeNull();
            expect($event->refKind)->toBe('feedback');
            expect($event->refId)->toBe($feedbackDto->id);
        });
    });

    describe('authorization', function () {
        it('denies create/update/delete to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER]);
            $this->actingAs($user);

            /** @var StoryRefPublicApi $api */
            $api = app(StoryRefPublicApi::class);

            $write = new FeedbackWriteDto(
                slug: 'comments',
                name: 'Comments',
                description: 'Comments desc',
                is_active: true,
                order: 1,
            );

            expect(fn () => $api->createFeedback($write))->toThrow(AuthorizationException::class);
        });
    });
});
