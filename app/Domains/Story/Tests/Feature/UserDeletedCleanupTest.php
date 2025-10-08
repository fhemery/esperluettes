<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('deletes all stories (with chapters and comments) authored by a user when the user is deleted', function () {
    // Arrange
    $author = alice($this, roles: [Roles::USER_CONFIRMED]);
    $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

    // Create a public story by the author with 2 published chapters
    $story = publicStory('Will Be Wiped', $author->id);
    $c1 = createPublishedChapter($this, $story, $author, ['title' => 'Ch 1', 'content' => '<p>Alpha</p>']);
    $c2 = createPublishedChapter($this, $story, $author, ['title' => 'Ch 2', 'content' => '<p>Beta</p>']);

    // Add comments on both chapters by a viewer (authors cannot post root comments per policy)
    /** @var CommentPublicApi $comments */
    $comments = app(CommentPublicApi::class);
    $this->actingAs($viewer);
    $root1 = $comments->create(new CommentToCreateDto('chapter', (int) $c1->id, str_repeat('x', 160), null));
    $comments->create(new CommentToCreateDto('chapter', (int) $c1->id, str_repeat('x', 40), $root1));
    $comments->create(new CommentToCreateDto('chapter', (int) $c2->id, str_repeat('x', 160), null));

    // Sanity: story is visible and comments exist
    $this->get(route('stories.show', ['slug' => $story->slug]))->assertOk()->assertSee('Will Be Wiped');
    $list1 = $comments->getFor('chapter', (int) $c1->id, page: 1, perPage: 10);
    $list2 = $comments->getFor('chapter', (int) $c2->id, page: 1, perPage: 10);
    expect($list1->total)->toBe(1);
    expect($list2->total)->toBe(1);

    // Act: delete the user via real flow (ensures UserDeleted event fires)
    deleteUser($this, $author);

    // Assert: story route now 404s
    $this->get(route('stories.show', ['slug' => $story->slug]))->assertNotFound();

    // Comments should be gone for both chapters
    // Re-authenticate as a valid viewer: deleteUser() changed auth context
    $this->actingAs($viewer);
    $after1 = $comments->getFor('chapter', (int) $c1->id, page: 1, perPage: 10);
    $after2 = $comments->getFor('chapter', (int) $c2->id, page: 1, perPage: 10);
    expect($after1->total)->toBe(0);
    expect($after2->total)->toBe(0);

    // Hard check: chapters are removed from DB
    $this->assertDatabaseMissing('story_chapters', ['id' => $c1->id]);
    $this->assertDatabaseMissing('story_chapters', ['id' => $c2->id]);
});
