<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Public\Events\ChapterUnpublishedByModeration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Chapter moderation - Unpublish', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        // Create as published
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Published Chap']);
        $this->slug = $this->chapter->slug;
        $this->targetUrl = "/chapters/{$this->slug}/moderation/unpublish";
        $this->referer = route('chapters.show', ['storySlug' => $this->story->slug, 'chapterSlug' => $this->chapter->slug]);
    });

    describe('Access', function () {
        it('redirects guests to login when accessing moderation route', function () {
            Auth::logout();
            $this->post($this->targetUrl)
                ->assertRedirect('/login');
        });

        it('denies access to non-moderators (user, user-confirmed) by redirecting to dashboard', function () {
            $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($confirmed)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to access and redirects to story with success message', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect(route('stories.show', ['slug' => $this->story->slug]))
                ->assertSessionHas('success', __('story::moderation.unpublish.success'));
        });
    });

    describe('Unpublish action', function () {
        it('sets chapter status to not_published in database', function () {
            // Precondition
            expect($this->chapter->status)->toBe(Chapter::STATUS_PUBLISHED);

            // Act
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect(route('stories.show', ['slug' => $this->story->slug]))
                ->assertSessionHas('success', __('story::moderation.unpublish.success'));

            // Assert DB
            $this->chapter->refresh();
            expect($this->chapter->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
        });

        it('emits ChapterUnpublishedByModeration event when moderator unpublishes the chapter', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect();

            /** @var ChapterUnpublishedByModeration $event */
            $event = latestEventOf(ChapterUnpublishedByModeration::name(), ChapterUnpublishedByModeration::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($this->story->id);
            expect($event->chapterId)->toBe($this->chapter->id);
        });

        it('is a no-op if already not published (keeps status)', function () {
            $this->chapter->update(['status' => Chapter::STATUS_NOT_PUBLISHED]);

            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect();

            $this->chapter->refresh();
            expect($this->chapter->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
        });
    });
});
