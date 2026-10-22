<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Soft deleting stories after user deactivation', function () {

    it('soft-deletes authored stories and their chapters, and hides them from public API', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story and one published chapter authored by the user
        $story = publicStory('Deactivation Story', $user->id);
        $chapter = createPublishedChapter($this, $story, $user);

        // Sanity: public API counts authored stories
        /** @var StoryPublicApi $publicApi */
        $publicApi = app(StoryPublicApi::class);
        expect($publicApi->countAuthoredStories($user->id))->toBe(1);

        // Act: deactivate via AuthPublicApi (emits UserDeactivated)
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);

        // Assert: public API no longer counts authored stories
        expect($publicApi->countAuthoredStories($user->id))->toBe(0);

        // Assert: story and chapter are soft-deleted
        $s = Story::withTrashed()->find($story->id);
        expect($s)->not->toBeNull();
        expect(method_exists($s, 'trashed') ? $s->trashed() : null)->toBeTrue();

        $c = Chapter::withTrashed()->where('id', $chapter->id)->first();
        expect($c)->not->toBeNull();
        expect(method_exists($c, 'trashed') ? $c->trashed() : null)->toBeTrue();
    });
});

describe('Soft deleting co-authored stories after user deactivation', function () {

    it('keeps a co-authored story and its chapters visible when only one of its authors is deactivated', function () {
        $admin = admin($this);
        $alice = alice($this);
        $bob = bob($this);
        $reader = carol($this);

        $story = publicStory('Shared Story', $alice->id);
        addCollaborator($story->id, $bob->id, 'author');
        $chapter = createPublishedChapter($this, $story, $alice);

        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($alice->id);

        expect(app(StoryPublicApi::class)->countAuthoredStories($bob->id))->toBe(1);
        expect(Story::withTrashed()->find($story->id)->trashed())->toBeFalse();
        expect(Chapter::withTrashed()->find($chapter->id)->trashed())->toBeFalse();

        $this->actingAs($reader)
            ->get(route('stories.show', ['slug' => $story->slug]))
            ->assertOk();
    });

    it('soft-deletes a co-authored story once its last active author is deactivated', function () {
        $admin = admin($this);
        $alice = alice($this);
        $bob = bob($this);

        $story = publicStory('Shared Story', $alice->id);
        addCollaborator($story->id, $bob->id, 'author');
        $chapter = createPublishedChapter($this, $story, $alice);

        $this->actingAs($admin);
        $api = app(AuthPublicApi::class);
        $api->deactivateUserById($alice->id);
        $api->deactivateUserById($bob->id);

        expect(Story::withTrashed()->find($story->id)->trashed())->toBeTrue();
        expect(Chapter::withTrashed()->find($chapter->id)->trashed())->toBeTrue();
    });

    it('soft-deletes the sole-authored story but keeps the co-authored one of the same user', function () {
        $admin = admin($this);
        $alice = alice($this);
        $bob = bob($this);

        $solo = publicStory('Solo Story', $alice->id);
        $shared = publicStory('Shared Story', $alice->id);
        addCollaborator($shared->id, $bob->id, 'author');

        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($alice->id);

        expect(Story::withTrashed()->find($solo->id)->trashed())->toBeTrue();
        expect(Story::withTrashed()->find($shared->id)->trashed())->toBeFalse();
    });

    it('does not count a deleted co-author as active', function () {
        $admin = admin($this);
        $alice = alice($this);
        $bob = bob($this);

        $story = publicStory('Shared Story', $alice->id);
        addCollaborator($story->id, $bob->id, 'author');

        // Bob's account vanishes while his author row remains
        \Illuminate\Support\Facades\DB::table('users')->where('id', $bob->id)->delete();

        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($alice->id);

        expect(Story::withTrashed()->find($story->id)->trashed())->toBeTrue();
    });

    it('looks up author activity in a single Auth call for many stories', function () {
        $admin = admin($this);
        $alice = alice($this);
        $bob = bob($this);
        $carol = carol($this);

        $a = publicStory('Story A', $alice->id);
        $b = publicStory('Story B', $alice->id);
        $c = publicStory('Story C', $alice->id);
        addCollaborator($a->id, $bob->id, 'author');
        addCollaborator($b->id, $bob->id, 'author');
        addCollaborator($c->id, $carol->id, 'author');

        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($carol->id);

        $lookups = 0;
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$lookups) {
            if (preg_match('/from [`"]users[`"] where [`"]id[`"] in/i', $q->sql)) {
                $lookups++;
            }
        });
        app(AuthPublicApi::class)->deactivateUserById($alice->id);
        expect($lookups)->toBe(1);

        expect(Story::withTrashed()->find($a->id)->trashed())->toBeFalse();
        expect(Story::withTrashed()->find($b->id)->trashed())->toBeFalse();
        expect(Story::withTrashed()->find($c->id)->trashed())->toBeTrue();
    });
});
