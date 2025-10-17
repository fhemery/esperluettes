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

describe('Restoring stories after user reactivation', function () {

    it('restores authored stories and chapters and makes them visible again', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story and published chapter
        $story = publicStory('Reactivation Story', $user->id);
        $chapter = createPublishedChapter($this, $story, $user);

        /** @var StoryPublicApi $publicApi */
        $publicApi = app(StoryPublicApi::class);
        expect($publicApi->countAuthoredStories($user->id))->toBe(1);

        // Deactivate -> soft-delete
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);
        expect($publicApi->countAuthoredStories($user->id))->toBe(0);
        $s = Story::withTrashed()->find($story->id);
        expect($s->trashed())->toBeTrue();
        $c = Chapter::withTrashed()->find($chapter->id);
        expect($c->trashed())->toBeTrue();

        // Act: reactivate
        app(AuthPublicApi::class)->activateUserById($user->id);

        // Assert: public API shows authored stories again
        expect($publicApi->countAuthoredStories($user->id))->toBe(1);

        // Assert: models restored
        $s = Story::withTrashed()->find($story->id);
        expect($s->trashed())->toBeFalse();
        $c = Chapter::withTrashed()->find($chapter->id);
        expect($c->trashed())->toBeFalse();
    });
});
