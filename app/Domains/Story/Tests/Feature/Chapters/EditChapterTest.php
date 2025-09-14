<?php

use App\Domains\Story\Events\ChapterPublished;
use App\Domains\Story\Events\ChapterUnpublished;
use App\Domains\Story\Events\ChapterUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Story\Models\Chapter;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Editing a chapter', function () {


    it('allows the author to access the chapter edit form', function () {
        $author = alice($this);
        $story = publicStory('Story A', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 1']);

        $this->actingAs($author);
        $resp = $this->get('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit');
        $resp->assertOk();
        // Ensure form fields are present
        $resp->assertSee('name="title"', false);
        $resp->assertSee('name="content"', false);
    });

    it('returns 404 for non-author accessing edit form', function () {
        $author = alice($this);
        $other = bob($this);
        $story = publicStory('Story B', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 2']);

        $this->actingAs($other);
        $this->get('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')->assertNotFound();
    });

    it('updates chapter with sanitized content, regenerates slug base, keeps id suffix, and sets first_published_at on first publish', function () {
        $author = alice($this);
        $story = publicStory('Story C', $author->id);
        // Start as draft
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Old Title']);
        $oldId = $chapter->id;

        $this->actingAs($author);
        $payload = [
            'title' => 'New Title',
            'author_note' => '<script>alert(1)</script><p>Note</p>',
            'content' => '<h1>  New <em>Content</em></h1>',
            'published' => '1',
        ];
        $resp = $this->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);
        $resp->assertRedirect();

        $chapterRefreshed = Chapter::query()->findOrFail($oldId);
        expect($chapterRefreshed->title)->toBe('New Title');
        // slug should end with -id suffix and base updated
        expect(str_ends_with($chapterRefreshed->slug, '-' . $oldId))->toBeTrue();
        expect(str_starts_with($chapterRefreshed->slug, 'new-title'))->toBeTrue();
        // status and first_published_at
        expect($chapterRefreshed->status)->toBe(Chapter::STATUS_PUBLISHED);
        expect($chapterRefreshed->first_published_at)->not->toBeNull();
        // content sanitized (no <script>)
        expect(str_contains($chapterRefreshed->content, '<script>'))->toBeFalse();
    });

    it('fails validation when content becomes empty after purification', function () {
        $author = alice($this);
        $story = publicStory('Story D', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 4']);

        $this->actingAs($author);
        // content with only empty block
        $payload = [
            'title' => 'Still Title',
            'author_note' => null,
            'content' => '   ',
        ];
        $resp = $this->from('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')
            ->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

        $resp->assertRedirect();
        $resp->assertSessionHasErrors(['content']);
    });

    it('fails validation when author note exceeds logical 1000 chars', function () {
        $author = alice($this);
        $story = publicStory('Story E', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 5']);

        $this->actingAs($author);
        $long = str_repeat('a', 1001);
        $payload = [
            'title' => 'T',
            'author_note' => '<p>' . $long . '</p>',
            'content' => '<p>Ok</p>',
        ];
        $resp = $this->from('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')
            ->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

        $resp->assertRedirect();
        $resp->assertSessionHasErrors(['author_note']);
    });

    it('should remove the story last_published if the last chapter is unpublished', function () {
        $author = alice($this);
        $story = publicStory('Story F', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 6']);

        $this->actingAs($author);
        $payload = [
            'title' => 'New Title',
            'author_note' => '<script>alert(1)</script><p>Note</p>',
            'content' => '<h1>  New <em>Content</em></h1>',
            'published' => '0',
        ];
        $resp = $this->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

        $resp->assertRedirect();

        $storyRefreshed = getStory($story->id);
        expect($storyRefreshed->last_chapter_published_at)->toBeNull();
    });

    it('should add back the story last_published if the last chapter is republished', function () {
        $author = alice($this);
        $story = publicStory('Story F', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 6']);

        $this->actingAs($author);
        $payload = [
            'title' => 'New Title',
            'author_note' => '<script>alert(1)</script><p>Note</p>',
            'content' => '<h1>  New <em>Content</em></h1>',
            'published' => '0',
        ];
        $resp = $this->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

        $resp->assertRedirect();

        $payload = [
            'title' => 'New Title',
            'author_note' => '<script>alert(1)</script><p>Note</p>',
            'content' => '<h1>  New <em>Content</em></h1>',
            'published' => '1',
        ];
        $resp = $this->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

        $resp->assertRedirect();

        $storyRefreshed = getStory($story->id);
        expect($storyRefreshed->last_chapter_published_at)->not->toBeNull();
    });

    describe('Events', function () {
        it('emits Chapter.Updated with before/after snapshots when editing a chapter', function () {
            $user = alice($this);
            $this->actingAs($user);

            $story = createStoryForAuthor($user->id, ['title' => 'Parent Story']);
            /** @var Chapter $chapter */
            $chapter = createUnpublishedChapter($this, $story, $user, [
                'title' => 'Old Title',
                'content' => '<p>Old content</p>',
            ]);

            $resp = $this->from('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')
                ->put(route('chapters.update', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]), [
                    'title' => 'New Title',
                    'author_note' => null,
                    'content' => '<p>New content</p>',
                    'published' => '0',
                ]);
            $resp->assertRedirect();

            /** @var ChapterUpdated|null $event */
            $event = latestEventOf(ChapterUpdated::name(), ChapterUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($story->id);
            expect($event->before->id)->toBe($chapter->id);
            expect($event->after->title)->toBe('New Title');
            expect($event->after->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
            expect($event->after->wordCount)->toBeGreaterThan(0);
            expect($event->after->charCount)->toBeGreaterThan(0);
        });

        it('emits Chapter.Published when transitioning from draft to published', function () {
            $user = alice($this);
            $this->actingAs($user);

            $story = createStoryForAuthor($user->id, ['title' => 'Publish Story']);
            $chapter = createUnpublishedChapter($this, $story, $user, [
                'title' => 'To Publish',
                'content' => '<p>Draft content</p>',
                'published' => '0',
            ]);

            $resp = $this->put(route('chapters.update', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]), [
                'title' => 'To Publish',
                'author_note' => null,
                'content' => '<p>Published content</p>',
                'published' => '1',
            ]);
            $resp->assertRedirect();

            /** @var ChapterPublished|null $event */
            $event = latestEventOf(ChapterPublished::name(), ChapterPublished::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($story->id);
            expect($event->chapter->id)->toBe($chapter->id);
            expect($event->chapter->status)->toBe(Chapter::STATUS_PUBLISHED);
        });

        it('emits Chapter.Unpublished when transitioning from published to draft', function () {
            $user = alice($this);
            $this->actingAs($user);

            $story = createStoryForAuthor($user->id, ['title' => 'Unpublish Story']);
            $chapter = createPublishedChapter($this, $story, $user, [
                'title' => 'To Unpublish',
                'content' => '<p>Some content</p>',
            ]);

            $resp = $this->put(route('chapters.update', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]), [
                'title' => 'To Unpublish',
                'author_note' => null,
                'content' => '<p>Some content</p>',
                'published' => '0',
            ]);
            $resp->assertRedirect();

            /** @var ChapterUnpublished|null $event */
            $event = latestEventOf(ChapterUnpublished::name(), ChapterUnpublished::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($story->id);
            expect($event->chapter->id)->toBe($chapter->id);
            expect($event->chapter->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
        });
    });
});
