<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domains\Story\Public\Events\StoryDeleted;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Auth\Public\Api\Roles;

uses(TestCase::class, RefreshDatabase::class);

it('allows an author to hard delete their story and then show returns 404', function () {
    $author = alice($this);
    $story = publicStory('My Story', $author->id);

    // Act
    $this->actingAs($author)->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertRedirect(route('stories.index'))
        ->assertSessionHas('status');

    // Assert via controller: show should now 404
    $this->get(route('stories.show', ['slug' => $story->slug]))
        ->assertNotFound();
});

it('deletes chapter comments when deleting a story', function () {
    $author = alice($this, roles: [Roles::USER_CONFIRMED]);
    $this->actingAs($author);

    // Create story and two chapters with content
    $story = publicStory('Comments Go Away', $author->id);
    $c1 = createPublishedChapter($this, $story, $author, ['title' => 'Ch 1', 'content' => '<p>Alpha beta gamma</p>']);
    $c2 = createPublishedChapter($this, $story, $author, ['title' => 'Ch 2', 'content' => '<p>Delta epsilon</p>']);

    /** @var CommentPublicApi $comments */
    $comments = app(CommentPublicApi::class);

    // Post comments as a non-author viewer (authors cannot post root per policy)
    $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);
    $this->actingAs($viewer);

    $long = str_repeat('x', 160);
    // Add comments on both chapters (roots + a reply)
    $root1 = $comments->create(new CommentToCreateDto('chapter', (int) $c1->id, $long, null));
    $comments->create(new CommentToCreateDto('chapter', (int) $c1->id, $long, $root1));
    $comments->create(new CommentToCreateDto('chapter', (int) $c2->id, $long, null));

    // Sanity check: comments exist
    $list1 = $comments->getFor('chapter', (int) $c1->id, page: 1, perPage: 10);
    $list2 = $comments->getFor('chapter', (int) $c2->id, page: 1, perPage: 10);
    expect($list1->total)->toBe(1);
    expect($list2->total)->toBe(1);

    // Delete story (as author)
    $this->actingAs($author)->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertRedirect(route('stories.index'));

    // Comments should be gone for both chapters
    $after1 = $comments->getFor('chapter', (int) $c1->id, page: 1, perPage: 10);
    $after2 = $comments->getFor('chapter', (int) $c2->id, page: 1, perPage: 10);
    expect($after1->total)->toBe(0);
    expect($after2->total)->toBe(0);
});

it('returns 404 to non-author attempting to delete; show still works', function () {
    $author = alice($this);
    $intruder = bob($this);
    $story = publicStory('Other Story', $author->id);

    $this->actingAs($intruder)
        ->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertNotFound();

    // Story still visible (public)
    $this->get(route('stories.show', ['slug' => $story->slug]))
        ->assertOk()
        ->assertSee('Other Story');
});

it('redirects guest to login on delete; show still works', function () {
    $author = alice($this);
    $story = publicStory('Login Story', $author->id);

    $this->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertRedirect(); // Typically to login

    // Story still visible (public)
    $this->get(route('stories.show', ['slug' => $story->slug]))
        ->assertOk()
        ->assertSee('Login Story');
});

it('emits Story.Deleted with story and chapter snapshots on delete', function () {
    $author = alice($this);
    $this->actingAs($author);

    // Create story via helper and add chapters through HTTP endpoint to follow real flow
    $story = publicStory('To Be Deleted', $author->id);
    createPublishedChapter($this, $story, $author, ['title' => 'Ch 1', 'content' => '<p>One two three</p>']);
    createPublishedChapter($this, $story, $author, ['title' => 'Ch 2', 'content' => '<p>Four five</p>']);

    // Delete via controller
    $this->delete(route('stories.destroy', ['slug' => $story->slug]))
        ->assertRedirect(route('stories.index'));

    /** @var StoryDeleted|null $event */
    $event = app(EventPublicApi::class)->latest(StoryDeleted::name());
    expect($event)->not->toBeNull();
    expect($event)->toBeInstanceOf(StoryDeleted::class);

    // Story snapshot basics
    $s = $event->story;
    expect($s->title)->toBe('To Be Deleted');
    expect($s->storyId)->toBeInt();

    // Chapters snapshots present and have ids/titles
    expect($event->chapters)->toBeArray();
    expect(count($event->chapters))->toBeGreaterThanOrEqual(2);
    $titles = array_map(fn($cs) => $cs->title, $event->chapters);
    expect($titles)->toContain('Ch 1');
    expect($titles)->toContain('Ch 2');
});
