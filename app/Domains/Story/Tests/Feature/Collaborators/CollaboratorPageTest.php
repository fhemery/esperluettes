<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Collaborator management page', function () {
    it('allows authors to access the collaborator management page', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);

        $response = $this->actingAs($author)->get(route('stories.collaborators.index', ['slug' => $story->slug]));

        $response->assertOk();
        $response->assertSee(__('story::collaborators.page_title', ['title' => $story->title]));
    });

    it('denies non-authors from accessing the collaborator management page', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);

        $response = $this->actingAs($reader)->get(route('stories.collaborators.index', ['slug' => $story->slug]));

        $response->assertNotFound();
    });

    it('lists all collaborators with authors first', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $betaReader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $response = $this->actingAs($author)->get(route('stories.collaborators.index', ['slug' => $story->slug]));

        $response->assertOk();
        $response->assertSee(__('story::collaborators.roles.author'));
        $response->assertSee(__('story::collaborators.roles.beta_reader'));
    });

    it('shows leave icon for author when there are other authors', function () {
        $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
        $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author1->id);
        addCollaborator($story->id, $author2->id, 'author');

        $response = $this->actingAs($author1)->get(route('stories.collaborators.index', ['slug' => $story->slug]));

        $response->assertOk();
        $response->assertSee('logout'); // leave icon
    });

    it('does not show leave icon when user is the only author', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);

        $response = $this->actingAs($author)->get(route('stories.collaborators.index', ['slug' => $story->slug]));

        $response->assertOk();
        // The leave route should not be present in the page when user is the only author
        $response->assertDontSee(route('stories.collaborators.leave', ['slug' => $story->slug]));
    });

    it('shows remove button for beta-readers', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $betaReader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $response = $this->actingAs($author)->get(route('stories.collaborators.index', ['slug' => $story->slug]));

        $response->assertOk();
        $response->assertSee('person_remove'); // remove icon
    });

    describe('Search and add a collaborator', function () {
        it('allows adding a beta-reader', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $newCollab = bob($this, roles: [Roles::USER]);
            $story = publicStory('My Story', $author->id);

            $response = $this->actingAs($author)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$newCollab->id],
                'role' => 'beta-reader',
            ]);

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            $this->assertDatabaseHas('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $newCollab->id,
                'role' => 'beta-reader',
            ]);
        });

        it('allows adding an author (confirmed user only)', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $newAuthor = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author->id);

            $response = $this->actingAs($author)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$newAuthor->id],
                'role' => 'author',
            ]);

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            $this->assertDatabaseHas('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $newAuthor->id,
                'role' => 'author',
            ]);
        });

        it('rejects adding non-confirmed user as author', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $nonConfirmed = bob($this, roles: [Roles::USER]);
            $story = publicStory('My Story', $author->id);

            $response = $this->actingAs($author)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$nonConfirmed->id],
                'role' => 'author',
            ]);

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            $response->assertSessionHas('error');
            $this->assertDatabaseMissing('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $nonConfirmed->id,
            ]);
        });

        it('upgrades beta-reader to author', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $betaReader = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author->id);
            addCollaborator($story->id, $betaReader->id, 'beta-reader');

            $response = $this->actingAs($author)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$betaReader->id],
                'role' => 'author',
            ]);

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            $this->assertDatabaseHas('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $betaReader->id,
                'role' => 'author',
            ]);
        });

        it('is a no-op when assigning beta-reader to an author', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            $response = $this->actingAs($author1)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$author2->id],
                'role' => 'beta-reader',
            ]);

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            // Should still be author, not downgraded
            $this->assertDatabaseHas('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $author2->id,
                'role' => 'author',
            ]);
        });

        it('is a no-op when adding same role twice', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $betaReader = bob($this, roles: [Roles::USER]);
            $story = publicStory('My Story', $author->id);
            addCollaborator($story->id, $betaReader->id, 'beta-reader');

            $response = $this->actingAs($author)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$betaReader->id],
                'role' => 'beta-reader',
            ]);

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            // Should only have one entry
            $count = DB::table('story_collaborators')
                ->where('story_id', $story->id)
                ->where('user_id', $betaReader->id)
                ->count();
            expect($count)->toBe(1);
        });

        it('denies non-authors from adding collaborators', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
            $newCollab = carol($this, roles: [Roles::USER]);
            $story = publicStory('My Story', $author->id);

            $response = $this->actingAs($reader)->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$newCollab->id],
                'role' => 'beta-reader',
            ]);

            $response->assertNotFound();
        });
    });

    describe('Removing collaborators', function () {
        it('allows removing a beta-reader', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $betaReader = bob($this, roles: [Roles::USER]);
            $story = publicStory('My Story', $author->id);
            addCollaborator($story->id, $betaReader->id, 'beta-reader');

            $response = $this->actingAs($author)->delete(route('stories.collaborators.destroy', [
                'slug' => $story->slug,
                'targetUserId' => $betaReader->id,
            ]));

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            $this->assertDatabaseMissing('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $betaReader->id,
            ]);
        });

        it('does not allow removing an author', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            $response = $this->actingAs($author1)->delete(route('stories.collaborators.destroy', [
                'slug' => $story->slug,
                'targetUserId' => $author2->id,
            ]));

            $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
            $response->assertSessionHas('error');
            // Author should still exist
            $this->assertDatabaseHas('story_collaborators', [
                'story_id' => $story->id,
                'user_id' => $author2->id,
                'role' => 'author',
            ]);
        });
    });
});