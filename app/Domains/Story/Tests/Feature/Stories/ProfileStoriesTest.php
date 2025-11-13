<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('Story list in profile tab', function () {

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
        addCollaborator($private->id, $contrib->id, 'betareader');
        
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
        $resp->assertSee('story::profile.new-story');
    });

    it('should not show a new Story button for the owner without ' . Roles::USER_CONFIRMED . ' role', function () {
        $owner = alice($this, roles: [Roles::USER]);
        $slug = profileSlugFromApi($owner->id);

        $resp = $this->actingAs($owner)->get("/profiles/{$slug}/stories");

        $resp->assertOk();
        $resp->assertDontSee('story::profile.new-story');
    });

    describe('story list display', function () {

        it('does not list author names in the profile stories partial', function () {
            $owner = alice($this);
            $slug = profileSlugFromApi($owner->id);

            publicStory('Some Story', $owner->id);

            $resp = $this->get("/profiles/{$slug}/stories");

            $resp->assertOk();
            // Hidden authors mean we should not render the by-label key used elsewhere
            $resp->assertDontSee('story::shared.by');
        });

        it('does show words chapter count if there is at least one chapter', function () {
            $owner = alice($this);
            $slug = profileSlugFromApi($owner->id);

            $story = publicStory('Some Story', $owner->id);
            createPublishedChapter($this, $story, $owner);

            $resp = $this->get("/profiles/{$slug}/stories");

            $resp->assertOk();
            $resp->assertSee('story::shared.metrics.words');
            $resp->assertSee('story::shared.metrics.chapters');
        });

        it('does not show word count if there are no chapters', function () {
            $owner = alice($this);
            $slug = profileSlugFromApi($owner->id);

            publicStory('No Chapters Story', $owner->id);

            $resp = $this->get("/profiles/{$slug}/stories");

            $resp->assertOk();
            $resp->assertDontSee('story::shared.metrics.words');
            $resp->assertSee('story::shared.metrics.chapters');
        });
    });

    describe('Regarding chapter credits', function () {
        it('shows chapter credits badge with 5 for a confirmed user profile', function () {
            $owner = alice($this); // confirmed by default
            $slug = profileSlugFromApi($owner->id);

            $resp = $this->actingAs($owner)->get("/profiles/{$slug}/stories");

            $resp->assertOk();
            // Badge renders a material icon label then the number
            $resp->assertSeeInOrder(['menu_book', '5']);
        });

        it('shows 4 after the owner creates one chapter', function () {
            $owner = alice($this);
            $slug = profileSlugFromApi($owner->id);

            $story = publicStory('Story A', $owner->id);
            // Create a chapter via helper (uses HTTP/service path)
            createUnpublishedChapter($this, $story, $owner, ['title' => 'C1']);

            $resp = $this->get("/profiles/{$slug}/stories");

            $resp->assertOk();
            $resp->assertSeeInOrder(['menu_book', '4']);
        });

        it('does not show the counter to other viewers', function () {
            $owner = alice($this);
            $viewer = bob($this);
            $slug = profileSlugFromApi($owner->id);

            $resp = $this->actingAs($viewer)->get("/profiles/{$slug}/stories");
            $resp->assertOk();
            $resp->assertDontSee('menu_book');
        });

        it('shows 6 when the owner posts a root comment on someone else\'s published chapter', function () {
            $commenter = alice($this);
            $author = bob($this);
            $slug = profileSlugFromApi($commenter->id);

            $foreignStory = publicStory('Foreign', $author->id);
            $chapter = createPublishedChapter($this, $foreignStory, $author, ['title' => 'F1']);

            // Owner posts a root comment on other user's chapter
            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Now visit profile stories for owner
            $resp = $this->get("/profiles/{$slug}/stories");
            $resp->assertOk();
            $resp->assertSeeInOrder(['menu_book', '6']);
        });

        it('still shows 5 when the owner posts a non-root (reply) comment on someone else\'s chapter', function () {
            $author = alice($this);
            $commenter = bob($this);
            $slug = profileSlugFromApi($commenter->id);

            $foreignStory = publicStory('Foreign', $author->id);
            $chapter = createPublishedChapter($this, $foreignStory, $author, ['title' => 'F1']);

            // Create an initial root comment by other user to reply to
            $this->actingAs($commenter);
            $commentId = createComment('chapter', $chapter->id, generateDummyText(150));
            
            // Create a reply to the root comment
            createComment('chapter', $chapter->id, generateDummyText(150), $commentId);

            $resp = $this->get("/profiles/{$slug}/stories");
            $resp->assertOk();
            $resp->assertSeeInOrder(['menu_book', '6']); // And not 7: the reply does not count
        });
    });

    describe('Reading statistics', function () {
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
});
