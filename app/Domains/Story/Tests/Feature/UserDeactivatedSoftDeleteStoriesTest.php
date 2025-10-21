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
