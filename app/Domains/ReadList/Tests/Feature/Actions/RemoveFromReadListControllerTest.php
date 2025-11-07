<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Models\ReadListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadList Controller - Remove from ReadList', function () {
    it('redirects guests to login when trying to remove story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $response = $this->delete("/readlist/{$story->id}");

        $response->assertRedirect('/login');
    });

    it('denies non-confirmed users from removing story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $nonConfirmed = bob($this, roles: []);

        $response = $this->actingAs($nonConfirmed)->delete("/readlist/{$story->id}");

        // Role middleware redirects to dashboard
        $response->assertRedirect(route('dashboard'));
    });

    it('allows verified users to remove story from read list', function ($role) {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this, roles: [$role]);

        // Add story to read list first
        ReadListEntry::create([
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);

        $response = $this->actingAs($reader)->delete("/readlist/{$story->id}");

        $response->assertRedirect();
        $response->assertSessionHas('info', __('readlist::button.removed_message'));

        // Verify story was removed
        $this->assertDatabaseMissing('read_list_entries', [
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);
    })->with([Roles::USER_CONFIRMED, Roles::USER]);

    it('returns 404 when story does not exist', function () {
        $reader = bob($this);

        $response = $this->actingAs($reader)->delete("/readlist/99999");

        $response->assertNotFound();
    });

    it('is idempotent when story not in read list', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this);

        // Story is NOT in read list
        $response = $this->actingAs($reader)->delete("/readlist/{$story->id}");

        $response->assertRedirect();
        // Should still succeed
        $response->assertSessionMissing('errors');

        // Should have no entries
        $this->assertDatabaseCount('read_list_entries', 0);
    });
});
