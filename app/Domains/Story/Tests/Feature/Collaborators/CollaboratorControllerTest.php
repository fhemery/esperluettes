
<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Leaving a story', function () {
    it('allows an author to leave when there are other authors', function () {
        $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
        $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author1->id);
        addCollaborator($story->id, $author2->id, 'author');

        $response = $this->actingAs($author1)->post(route('stories.collaborators.leave', ['slug' => $story->slug]));

        $response->assertRedirect(route('stories.show', ['slug' => $story->slug]));
        $this->assertDatabaseMissing('story_collaborators', [
            'story_id' => $story->id,
            'user_id' => $author1->id,
        ]);
    });

    it('does not allow the only author to leave', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $story = publicStory('My Story', $author->id);

        $response = $this->actingAs($author)->post(route('stories.collaborators.leave', ['slug' => $story->slug]));

        $response->assertRedirect(route('stories.collaborators.index', ['slug' => $story->slug]));
        $response->assertSessionHas('error');
        // Author should still exist
        $this->assertDatabaseHas('story_collaborators', [
            'story_id' => $story->id,
            'user_id' => $author->id,
            'role' => 'author',
        ]);
    });

    it('allows a beta-reader to leave', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $betaReader = bob($this, roles: [Roles::USER]);
        $story = publicStory('My Story', $author->id);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $response = $this->actingAs($betaReader)->post(route('stories.collaborators.leave', ['slug' => $story->slug]));

        $response->assertRedirect(route('stories.show', ['slug' => $story->slug]));
        $this->assertDatabaseMissing('story_collaborators', [
            'story_id' => $story->id,
            'user_id' => $betaReader->id,
        ]);
    });
});
