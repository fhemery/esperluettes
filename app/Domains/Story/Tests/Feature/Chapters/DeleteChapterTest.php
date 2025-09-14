<?php

use App\Domains\Story\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Story\Events\ChapterDeleted;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Delete chapter', function () {
    it('allows an author to hard delete a chapter and redirects to story page with flash', function () {
        $author = alice($this);
        $this->actingAs($author);
        $story = publicStory('Deletable Story', $author->id);

        // Create a published chapter via real endpoint
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'To Delete']);

        // Sanity
        expect(Chapter::query()->whereKey($chapter->id)->exists())->toBeTrue();

        // Delete
        $resp = $this->delete('/stories/' . $story->slug . '/chapters/' . $chapter->slug);
        $resp->assertRedirect(route('stories.show', ['slug' => $story->slug]));
        $resp->assertSessionHas('status', trans('story::chapters.deleted_success'));

        // Chapter gone
        expect(Chapter::query()->whereKey($chapter->id)->exists())->toBeFalse();
    });

    it('allows a co-author to delete a chapter', function () {
        $author = alice($this);
        $coauthor = bob($this);
        $this->actingAs($author);
        $story = publicStory('Team Delete', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Team Chapter']);

        // Add coauthor as author collaborator
        DB::table('story_collaborators')->insert([
            'story_id' => $story->id,
            'user_id' => $coauthor->id,
            'role' => 'author',
            'invited_by_user_id' => $author->id,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        // Act as co-author and delete
        $this->actingAs($coauthor);
        $resp = $this->delete('/stories/' . $story->slug . '/chapters/' . $chapter->slug);
        $resp->assertRedirect(route('stories.show', ['slug' => $story->slug]));
        expect(Chapter::query()->whereKey($chapter->id)->exists())->toBeFalse();
    });

    it('returns 404 when a non-author collaborator tries to delete', function () {
        $author = alice($this);
        $collab = bob($this);
        $this->actingAs($author);
        $story = publicStory('No Delete Perms', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Keep']);

        // Add collaborator with editor role (not author)
        DB::table('story_collaborators')->insert([
            'story_id' => $story->id,
            'user_id' => $collab->id,
            'role' => 'editor',
            'invited_by_user_id' => $author->id,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->actingAs($collab);
        $this->delete('/stories/' . $story->slug . '/chapters/' . $chapter->slug)->assertNotFound();

        // Still present
        expect(Chapter::query()->whereKey($chapter->id)->exists())->toBeTrue();
    });

    it('deletes associated reading progress via FK cascade', function () {
        $author = alice($this);
        $reader = bob($this);
        $this->actingAs($author);
        $story = publicStory('Cascade Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Progress Chapter']);

        // Reader marks as read (must not be author)
        $this->actingAs($reader);
        $this->post(route('chapters.read.mark', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertNoContent();

        // Ensure progress exists
        $countBefore = DB::table('story_reading_progress')->where('chapter_id', $chapter->id)->count();
        expect($countBefore)->toBe(1);

        // Delete as author
        $this->actingAs($author);
        $this->delete('/stories/' . $story->slug . '/chapters/' . $chapter->slug)->assertRedirect();

        // Progress rows removed
        $countAfter = DB::table('story_reading_progress')->where('chapter_id', $chapter->id)->count();
        expect($countAfter)->toBe(0);
    });

    it('returns 404 when story and chapter belong to different stories (slug id mismatch)', function () {
        $author = alice($this);
        $this->actingAs($author);

        // Two separate stories
        $storyA = publicStory('Story A', $author->id);
        $storyB = publicStory('Story B', $author->id);

        // Chapter belongs to Story A
        $chapterA = createPublishedChapter($this, $storyA, $author, ['title' => 'A1']);

        // Attempt to delete chapterA using storyB in the URL must 404
        $this->delete('/stories/' . $storyB->slug . '/chapters/' . $chapterA->slug)
            ->assertNotFound();

        // Chapter still exists
        expect(Chapter::query()->whereKey($chapterA->id)->exists())->toBeTrue();
    });

    it('should set story last_chapter_published_at to null if last published chapter is deleted', function () {
        $author = alice($this);
        $this->actingAs($author);
        $story = publicStory('Last Published', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Last Chapter']);

        $this->delete('/stories/' . $story->slug . '/chapters/' . $chapter->slug)->assertRedirect();

        $storyRefreshed = getStory($story->id);
        expect($storyRefreshed->last_chapter_published_at)->toBeNull();
    });

    it('deletes comments for the deleted chapter only', function () {
        $author = alice($this);
        $this->actingAs($author);
        $story = publicStory('Keep Others', $author->id);
        $c1 = createPublishedChapter($this, $story, $author, ['title' => 'To Purge']);
        $c2 = createPublishedChapter($this, $story, $author, ['title' => 'To Keep']);

        /** @var CommentPublicApi $comments */
        $comments = app(CommentPublicApi::class);

        // Post as a non-author viewer to satisfy policy and min length
        $viewer = bob($this);
        $this->actingAs($viewer);
        $long = str_repeat('y', 160);
        $comments->create(new CommentToCreateDto('chapter', (int) $c1->id, $long, null));
        $comments->create(new CommentToCreateDto('chapter', (int) $c2->id, $long, null));

        // Sanity: each chapter has one root comment
        expect($comments->getFor('chapter', (int) $c1->id, 1, 10)->total)->toBe(1);
        expect($comments->getFor('chapter', (int) $c2->id, 1, 10)->total)->toBe(1);

        // Delete c1 as author
        $this->actingAs($author);
        $this->delete('/stories/' . $story->slug . '/chapters/' . $c1->slug)->assertRedirect();

        // Comments for c1 gone, c2 still present
        expect($comments->getFor('chapter', (int) $c1->id, 1, 10)->total)->toBe(0);
        expect($comments->getFor('chapter', (int) $c2->id, 1, 10)->total)->toBe(1);
    });
    describe('Events', function () {
        it('is emitted when deleting a chapter and contains a snapshot', function () {
            $user = alice($this);
            $this->actingAs($user);

            $story = createStoryForAuthor($user->id, ['title' => 'Delete Story']);
            /** @var Chapter $chapter */
            $chapter = createUnpublishedChapter($this, $story, $user, [
                'title' => 'To Delete',
                'content' => '<p>Some content</p>',
            ]);

            $resp = $this->delete(route('chapters.destroy', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));
            $resp->assertRedirect();

            /** @var ChapterDeleted|null $event */
            $event = latestEventOf(ChapterDeleted::name(), ChapterDeleted::class);
            expect($event)->not->toBeNull();
            expect($event->storyId)->toBe($story->id);
            expect($event->chapter->id)->toBe($chapter->id);
            expect($event->chapter->title)->toBe('To Delete');
            expect($event->chapter->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
            expect($event->chapter->wordCount)->toBeGreaterThan(0);
            expect($event->chapter->charCount)->toBeGreaterThan(0);
        });
    });
});
