<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Public\Events\ChapterContentModerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Chapter moderation - Empty Content', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        // Create as published with some content
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, [
            'title' => 'Chapter With Content',
            'content' => '<p>Some text</p>',
        ]);
        $this->slug = $this->chapter->slug;
        $this->targetUrl = "/chapters/{$this->slug}/moderation/empty-content";
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

        it('allows moderator to access and redirects back with success message', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('story::moderation.empty_chapter_content.success'));
        });
    });

    describe('Empty content', function () {
        it('sets the chapter content to empty string in database (not null) and updates UI', function () {
            // Precondition: content present on page
            $this->get($this->referer)
                ->assertOk()
                ->assertSee('Some text', false);

            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('story::moderation.empty_chapter_content.success'));

            // Assert DB
            $this->chapter->refresh();
            expect($this->chapter->content)->toBe('');

            // Assert UI
            $resp = $this->get($this->referer);
            $resp->assertOk();
            $resp->assertDontSee('Some text', false);
        });

        it('sets word_count to 0 when content is emptied', function () {
            // Precondition: word_count should be > 0 for non-empty content
            $this->chapter->refresh();
            expect($this->chapter->word_count)->toBeGreaterThan(0);

            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            $this->chapter->refresh();
            expect($this->chapter->word_count)->toBe(0);
        });

        it('emits ChapterContentModerated event when moderator empties the content', function () {
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var ChapterContentModerated $event */
            $event = latestEventOf(ChapterContentModerated::name(), ChapterContentModerated::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($this->story->id);
            expect($event->chapterId)->toBe($this->chapter->id);
        });

        it('clears the blocks of an advanced chapter as well as its content', function () {
            $this->actingAs($this->author);
            $this->put(route('chapters.update', [
                'storySlug' => $this->story->slug,
                'chapterSlug' => $this->chapter->slug,
            ]), [
                'title' => 'Chapter With Content',
                'mode' => 'advanced',
                'blocks_order' => 'b0',
                'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Some text</p>']],
                'published' => '1',
            ])->assertRedirect();

            $this->chapter->refresh();
            expect($this->chapter->content_blocks)->not->toBeNull();

            $moderatorUser = moderator($this);
            $this->actingAs($moderatorUser)
                ->post("/chapters/{$this->chapter->slug}/moderation/empty-content")
                ->assertRedirect();

            $this->chapter->refresh();
            expect($this->chapter->content)->toBe('');
            expect($this->chapter->content_blocks)->toBeNull();
        });

        it('does not resurrect the moderated text on a subsequent save', function () {
            $this->actingAs($this->author);
            $this->put(route('chapters.update', [
                'storySlug' => $this->story->slug,
                'chapterSlug' => $this->chapter->slug,
            ]), [
                'title' => 'Chapter With Content',
                'mode' => 'advanced',
                'blocks_order' => 'b0',
                'blocks' => ['b0' => ['type' => 'text', 'html' => '<p>Some text</p>']],
                'published' => '1',
            ])->assertRedirect();

            $moderatorUser = moderator($this);
            $this->actingAs($moderatorUser)
                ->post("/chapters/{$this->chapter->slug}/moderation/empty-content")
                ->assertRedirect();

            // The author saves again, in simple mode with empty-ish content.
            $this->actingAs($this->author);
            $this->put(route('chapters.update', [
                'storySlug' => $this->story->slug,
                'chapterSlug' => $this->chapter->fresh()->slug,
            ]), [
                'title' => 'Chapter With Content',
                'mode' => 'simple',
                'content' => '<p>Nouveau texte</p>',
                'published' => '1',
            ])->assertRedirect();

            $this->chapter->refresh();
            expect($this->chapter->content)->not->toContain('Some text');
            expect($this->chapter->content_blocks)->toBeNull();
        });

        it('is a no-op if already empty (keeps content as empty string)', function () {
            $this->chapter->update(['content' => '']);

            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            $this->chapter->refresh();
            expect($this->chapter->content)->toBe('');
        });
    });
});
