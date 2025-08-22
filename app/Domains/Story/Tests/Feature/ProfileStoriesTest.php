<?php

use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function profileSlugFor(Authenticatable $user): string
{
    /** @var ProfilePublicApi $api */
    $api = app(ProfilePublicApi::class);
    $dto = $api->getPublicProfile($user->id);
    if ($dto === null) {
        throw new RuntimeException('Profile not found for user id ' . $user->id);
    }
    return $dto->slug;
}

it('does not render the site navigation for the profile stories partial', function () {
    $owner = alice($this);
    $slug = profileSlugFor($owner);

    // Act
    $resp = $this->get("/profiles/{$slug}/stories");

    // Assert: partial HTML only (no layout navigation/header markers)
    $resp->assertOk();
    $resp->assertDontSee('<x-app-layout>');
    $resp->assertDontSee('<nav');
});

it('lists only public stories for guests', function () {
    $owner = alice($this);
    $slug = profileSlugFor($owner);

    $public = publicStory('Guest Public Story', $owner->id);
    $community = communityStory('Guest Community Story', $owner->id);
    $private = privateStory('Guest Private Story', $owner->id);

    $resp = $this->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($public->title);
    $resp->assertDontSee($community->title);
    $resp->assertDontSee($private->title);
});

it('returns 404 when profile slug does not exist', function () {
    $resp = $this->get('/profiles/non-existing-user-xyz/stories');
    $resp->assertNotFound();
});

it('lists public and community stories to logged-in viewers (non-owner)', function () {
    $owner = alice($this);
    $viewer = bob($this);
    $slug = profileSlugFor($owner);

    $public = publicStory('Public Story', $owner->id);
    $community = communityStory('Community Story', $owner->id);
    $private = privateStory('Private Story', $owner->id);

    $resp = $this->actingAs($viewer)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($public->title);
    $resp->assertSee($community->title);
    $resp->assertDontSee($private->title);
});

it('includes private stories when viewer is the owner', function () {
    $owner = alice($this);
    $slug = profileSlugFor($owner);

    $private = privateStory('Owner Private', $owner->id);

    $resp = $this->actingAs($owner)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($private->title);
});

it('includes private stories when viewer is a contributor', function () {
    $owner = alice($this);
    $contrib = bob($this);
    $slug = profileSlugFor($owner);

    $private = privateStory('Contributor Private', $owner->id);

    // Add bob as collaborator to the private story
    DB::table('story_collaborators')->insert([
        'story_id' => $private->id,
        'user_id' => $contrib->id,
        'role' => 'author',
        'invited_by_user_id' => $owner->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $resp = $this->actingAs($contrib)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($private->title);
});

it('lists only stories authored by the profile owner', function () {
    $owner = alice($this);
    $other = bob($this);
    $slug = profileSlugFor($owner);

    $owned = publicStory('Owned Story', $owner->id);
    $foreign = publicStory('Foreign Story', $other->id);

    $resp = $this->actingAs($other)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($owned->title);
    $resp->assertDontSee($foreign->title);
});

it('does not list author names in the profile stories partial', function () {
    $owner = alice($this);
    $slug = profileSlugFor($owner);

    publicStory('Some Story', $owner->id);

    $resp = $this->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    // Hidden authors mean we should not render the by-label key used elsewhere
    $resp->assertDontSee('story::shared.by');
});
