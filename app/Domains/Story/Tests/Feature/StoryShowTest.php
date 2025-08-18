<?php

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows public story details with title, description, authors and creation date', function () {
    // Arrange: author and public story
    $author = User::factory()->create();
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
    $author = User::factory()->create();
    $nonAuthor = User::factory()->create();
    $story = privateStory('Private Story', $author);

    $this->actingAs($nonAuthor);
    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('redirects guest to login for community story', function () {
    $author = User::factory()->create();
    $story = communityStory('Community Story', $author);

    $this->get('/stories/' . $story->slug)->assertRedirect('/login');
});

it('returns 404 for community story to unverified user', function () {
    $author = User::factory()->create();
    $unverified = User::factory()->create([ 'email_verified_at' => null ]);
    $story = communityStory('Community Story', $author);

    $this->actingAs($unverified);
    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('shows placeholder when description is empty', function () {
    $author = User::factory()->create();
    $story = publicStory('No Desc Story', $author, [ 'description' => '' ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('story::show.no_description');
});

it('lists multiple authors separated by comma', function () {
    $author1 = User::factory()->create(['name' => 'Alice']);
    $author2 = User::factory()->create(['name' => 'Bob']);
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
    $response->assertSee('Alice, Bob');
});
