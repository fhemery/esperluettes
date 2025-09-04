<?php

use App\Domains\Story\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

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
