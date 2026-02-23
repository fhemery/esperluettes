<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\CoverService;
use App\Domains\Story\Public\Events\StoryCoverModerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Story moderation - Remove Cover', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->slug = $this->story->slug;
        $this->targetUrl = "/stories/{$this->slug}/moderation/remove-cover";
        $this->referer = "/stories/{$this->slug}";
    });

    describe('Access', function () {
        it('redirects guests to login', function () {
            $this->post($this->targetUrl)
                ->assertRedirect('/login');
        });

        it('denies access to non-moderators by redirecting to dashboard', function () {
            $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($confirmed)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to access and redirects back', function () {
            $moderator = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);
        });
    });

    describe('Remove cover', function () {
        it('resets a themed cover to default', function () {
            $this->story->update(['cover_type' => Story::COVER_THEMED, 'cover_data' => 'fantasy']);
            expect($this->story->cover_type)->toBe(Story::COVER_THEMED);

            $moderator = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('story::moderation.remove_cover.success'));

            $this->story->refresh();
            expect($this->story->cover_type)->toBe(Story::COVER_DEFAULT);
            expect($this->story->cover_data)->toBeNull();
        });

        it('resets a custom cover to default and deletes the files', function () {
            Storage::fake('public');

            $this->story->update(['cover_type' => Story::COVER_CUSTOM]);

            $file = UploadedFile::fake()->image('cover.jpg', 900, 1200);
            $coverService = app(CoverService::class);
            $coverService->uploadCustomCover($this->story, $file);
            expect($coverService->hasCustomCover($this->story))->toBeTrue();

            $moderator = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('story::moderation.remove_cover.success'));

            $this->story->refresh();
            expect($this->story->cover_type)->toBe(Story::COVER_DEFAULT);
            expect($this->story->cover_data)->toBeNull();
            expect($coverService->hasCustomCover($this->story))->toBeFalse();
        });

        it('emits StoryCoverModerated event with storyId and storyOwnerId', function () {
            $this->story->update(['cover_type' => Story::COVER_THEMED, 'cover_data' => 'fantasy']);

            $moderator = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var StoryCoverModerated $event */
            $event = latestEventOf(StoryCoverModerated::name(), StoryCoverModerated::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($this->story->id);
            expect($event->storyOwnerId)->toBe($this->author->id);
        });

        it('is a no-op if cover is already default (no event emitted)', function () {
            expect($this->story->cover_type)->toBe(Story::COVER_DEFAULT);

            $moderator = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            $this->story->refresh();
            expect($this->story->cover_type)->toBe(Story::COVER_DEFAULT);

            $event = latestEventOf(StoryCoverModerated::name(), StoryCoverModerated::class);
            expect($event)->toBeNull();
        });
    });
});
