<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Models\ReadListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadList Controller - Add to ReadList', function () {
    it('redirects guests to login when trying to add story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $response = $this->post("/readlist/{$story->id}");

        $response->assertRedirect('/login');
    });

    it('denies non-verified users from adding story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $nonConfirmed = bob($this, roles: []);

        $response = $this->actingAs($nonConfirmed)->post("/readlist/{$story->id}");

        $response->assertRedirect(route('dashboard'));
    });

    it('allows verified users (confirmed and non confirmed) to add story to read list', function ($role) {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this, roles: [$role]);

        $response = $this->actingAs($reader)->post("/readlist/{$story->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', __('readlist::button.added_message'));

        // Verify story was added
        $this->assertDatabaseHas('read_list_entries', [
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);
    })->with([Roles::USER_CONFIRMED, Roles::USER]);

    it('prevents author from adding their own story', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);

        $response = $this->actingAs($author)->post("/readlist/{$story->id}");

        $response->assertForbidden();
    });

    it('returns 404 when story does not exist', function () {
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        $response = $this->actingAs($reader)->post("/readlist/99999");

        $response->assertNotFound();
    });

    it('is idempotent when story already in read list', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Add story to read list first
        ReadListEntry::create([
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);

        $response = $this->actingAs($reader)->post("/readlist/{$story->id}");

        $response->assertRedirect();
        // Should still succeed
        $response->assertSessionMissing('errors');

        // Should only have one entry
        $this->assertDatabaseCount('read_list_entries', 1);
    });

    it('forbids adding to readlist when user does not have access to story (community + USER)', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = communityStory('Test Story', $author->id);

        // USER (not confirmed) tries to add -> should be forbidden
        $reader = bob($this, roles: [Roles::USER]);
        $response = $this->actingAs($reader)->post("/readlist/{$story->id}");

        $response->assertForbidden();

        // Ensure no entry created
        $this->assertDatabaseMissing('read_list_entries', [
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);
    });

    it('forbids adding to readlist for private story when user is not an author', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = privateStory('Private Story', $author->id);

        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $response = $this->actingAs($reader)->post("/readlist/{$story->id}");

        $response->assertForbidden();
        $this->assertDatabaseMissing('read_list_entries', [
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);
    });

    it('allows adding to readlist for community story when user is user-confirmed', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = communityStory('Community Story', $author->id);

        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $response = $this->actingAs($reader)->post("/readlist/{$story->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', __('readlist::button.added_message'));

        $this->assertDatabaseHas('read_list_entries', [
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);
    });
});