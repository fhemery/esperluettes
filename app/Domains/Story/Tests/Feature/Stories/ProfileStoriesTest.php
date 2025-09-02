<?php

use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('does not render the site navigation for the profile stories partial', function () {
    $owner = alice($this);
    $slug = profileSlugFromApi($owner->id);

    // Act
    $resp = $this->get("/profiles/{$slug}/stories");

    // Assert: partial HTML only (no layout navigation/header markers)
    $resp->assertOk();
    $resp->assertDontSee('<x-app-layout>');
    $resp->assertDontSee('<nav');
});

it('lists only public stories for guests', function () {
    $owner = alice($this);
    $slug = profileSlugFromApi($owner->id);

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
    $slug = profileSlugFromApi($owner->id);

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
    $slug = profileSlugFromApi($owner->id);

    $private = privateStory('Owner Private', $owner->id);

    $resp = $this->actingAs($owner)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($private->title);
});

it('includes private stories when viewer is a contributor', function () {
    $owner = alice($this);
    $contrib = bob($this);
    $slug = profileSlugFromApi($owner->id);

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
    $slug = profileSlugFromApi($owner->id);

    $owned = publicStory('Owned Story', $owner->id);
    $foreign = publicStory('Foreign Story', $other->id);

    $resp = $this->actingAs($other)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee($owned->title);
    $resp->assertDontSee($foreign->title);
});

it('should show "My stories" instead of "Stories" and a new Story button for the owner', function () {
    $owner = alice($this);
    $slug = profileSlugFromApi($owner->id);

    $resp = $this->actingAs($owner)->get("/profiles/{$slug}/stories");
    $resp->assertOk();
    $resp->assertSee('story::profile.my-stories');
    $resp->assertSee('story::profile.new-story');
});

it('should not show a new Story button for the owner without '.Roles::USER_CONFIRMED.' role', function () {
    $owner = alice($this, roles: [Roles::USER]);
    $slug = profileSlugFromApi($owner->id);

    $resp = $this->actingAs($owner)->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    $resp->assertSee('story::profile.my-stories');
    $resp->assertDontSee('story::profile.new-story');
});

it('does not list author names in the profile stories partial', function () {
    $owner = alice($this);
    $slug = profileSlugFromApi($owner->id);

    publicStory('Some Story', $owner->id);

    $resp = $this->get("/profiles/{$slug}/stories");

    $resp->assertOk();
    // Hidden authors mean we should not render the by-label key used elsewhere
    $resp->assertDontSee('story::shared.by');
});

describe('Reading statistics', function (){
    it('shows total reads on profile stories cards', function () {
        $owner = alice($this);
        $slug = profileSlugFromApi($owner->id);
        $story = publicStory('Profile Total Reads', $owner->id);
        $chapter = createPublishedChapter($this, $story, $owner);

        $reader = bob($this);
        $this->actingAs($reader);
        markAsRead($this, $chapter)->assertNoContent();

        Auth::logout();
        $resp = $this->get('/profiles/' . $slug . '/stories');
        $resp->assertOk();
        $resp->assertSee('Profile Total Reads');
        $resp->assertSee('1');
    });
});