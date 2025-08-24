<?php

use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('redirects guests from edit page to login', function () {
    // Arrange: existing public story
    $author = alice($this, roles: ['user-confirmed']);
    $story = publicStory('Guest Edit Test', $author->id);

    // Act
    $resp = $this->get('/stories/' . $story->slug . '/edit');

    // Assert
    $resp->assertRedirect('/login');
});

it('allows the author to load edit page and update story', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Original Title', $author->id, ['description' => '<p>desc</p>']);

    // Load edit page
    $this->get('/stories/' . $story->slug . '/edit')
        ->assertOk()
        ->assertSee('story::edit.title');

    // Update
    $payload = [
        'title' => 'Updated Title',
        'description' => '<p>new desc</p>',
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
    ];

    $resp = $this->put('/stories/' . $story->slug, $payload);
    $resp->assertRedirect();

    // Reload model
    $story->refresh();

    // Slug base regenerated, id suffix preserved
    $newBase = Story::generateSlugBase('Updated Title');
    expect($story->slug)
        ->toStartWith($newBase . '-')
        ->and($story->slug)
        ->toEndWith('-' . $story->id);
});

it('allows a co-author with role author to update', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $coauthor = bob($this, roles: ['user-confirmed']);
    $story = publicStory('Team Story', $author->id);

    // Add coauthor as author
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $coauthor->id,
        'role' => 'author',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $this->actingAs($coauthor);

    $resp = $this->put('/stories/' . $story->slug, [
        'title' => 'Coauthored Title',
        'description' => '<p>x</p>',
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
    ]);

    $resp->assertRedirect();
    $story->refresh();
    expect($story->title)->toBe('Coauthored Title');
});

it('returns 404 for collaborator without author role', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $other = bob($this, roles: ['user-confirmed']);
    $story = publicStory('No Edit Perms', $author->id);

    // Add collaborator with non-author role
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $other->id,
        'role' => 'editor',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $this->actingAs($other);

    $this->get('/stories/' . $story->slug . '/edit')->assertNotFound();
    $this->put('/stories/' . $story->slug, [
        'title' => 'Should Fail',
        'description' => '<p>x</p>',
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
    ])->assertNotFound();
});

it('returns 404 for non-collaborator trying to edit', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $intruder = bob($this, roles: ['user-confirmed']);
    $story = publicStory('No Access', $author->id);

    $this->actingAs($intruder);

    $this->get('/stories/' . $story->slug . '/edit')->assertNotFound();
    $this->put('/stories/' . $story->slug, [
        'title' => 'Nope',
        'description' => '<p>x</p>',
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
    ])->assertNotFound();
});

it('301-redirects from old slug base to canonical after title change', function () {
    // Arrange
    $author = alice($this, roles: ['user-confirmed']);
    $this->actingAs($author);
    $story = publicStory('Old Title', $author->id);
    $oldSlug = $story->slug; // contains -id

    // Update title
    $this->put('/stories/' . $oldSlug, [
        'title' => 'New Canonical Title',
        'description' => '<p>desc</p>',
        'visibility' => Story::VIS_PUBLIC,
        'story_ref_type_id' => defaultStoryType()->id,
    ])->assertRedirect();

    $story->refresh();

    // Visiting the old slug-with-id should 301 to the new canonical slug
    $resp = $this->get('/stories/' . $oldSlug);
    $resp->assertStatus(301);
    $resp->assertRedirect('/stories/' . $story->slug);
});

it('denies edit access to co-authors without user-confirmed role (middleware)', function () {
    // Arrange: author is confirmed, coauthor is NOT (has only user role)
    $author = alice($this, roles: ['user-confirmed']);
    $coauthor = bob($this, roles: ['user']);
    $story = publicStory('Needs Confirmed Role', $author->id);

    // Add coauthor as author collaborator at story level
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $coauthor->id,
        'role' => 'author',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    // Act as non-confirmed coauthor
    $this->actingAs($coauthor);

    // Because routes are guarded by role:user-confirmed, middleware should redirect to dashboard
    $this->get('/stories/' . $story->slug . '/edit')->assertRedirect(route('dashboard'));

    // And update attempts should also be blocked by middleware
    $this->put('/stories/' . $story->slug, [
        'title' => 'Should Not Be Applied',
        'description' => '<p>x</p>',
        'visibility' => Story::VIS_PUBLIC,
    ])->assertRedirect(route('dashboard'));

    // Ensure story unchanged
    $story->refresh();
    expect($story->title)->toBe('Needs Confirmed Role');
});
