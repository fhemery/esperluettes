<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows public story details with title, description, authors and creation date', function () {
    // Arrange: author and public story
    $author = alice($this);
    $story = publicStory('Public Story', $author, [
        'description' => '<p>Some description</p>',
    ]);

    // Act
    $response = $this->get('/stories/' . $story->slug);

    // Assert
    $response->assertOk();
    $response->assertSee('Public Story');
    $response->assertSee('Some description');
    $response->assertSee($author->name);

    // Date shown in Y-m-d for non-fr locale in tests
    $response->assertSee($story->created_at->format('Y-m-d'));
});

it('returns 404 for private story to non-author', function () {
    $author = alice($this);
    $nonAuthor = bob($this);
    $story = privateStory('Private Story', $author);

    $this->actingAs($nonAuthor);
    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('redirects guest to login for community story', function () {
    $author = alice($this);
    $story = communityStory('Community Story', $author);

    Auth::logout();

    $this->get('/stories/' . $story->slug)->assertRedirect('/login');
});

it('returns 404 for community story to unverified user', function () {
    $author = alice($this);
    $unverified = bob($this, [], false);
    $story = communityStory('Community Story', $author);

    $this->actingAs($unverified);
    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('shows placeholder when description is empty', function () {
    $author = alice($this);
    $story = publicStory('No Desc Story', $author, [ 'description' => '' ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('story::show.no_description');
});

it('lists multiple authors separated by comma', function () {
    $author1 = alice($this);
    $author2 = bob($this);
    $story = publicStory('Coauthored Story', $author1);

    // Attach second author on pivot
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $author2->id,
        'role' => 'author',
        'invited_by_user_id' => $author1->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('Alice');
    $response->assertSee('Bob');
});

it('should add a link to author profile page', function () {
    $author = alice($this);
    $story = publicStory('Public Story', $author);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('/profile/alice');
});
