<?php

use Tests\TestCase;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

describe('Story public API', function () {
    
    describe('countAuthoredStories', function () {
        it('returns 0 for non-authors', function () {
            $api = app(StoryPublicApi::class);
            $user = alice($this);

            $resp = $api->countAuthoredStories($user->id);
            expect($resp)->toBe(0);
        });

        it('returns 1 for authors', function () {
            $api = app(StoryPublicApi::class);
            $user = alice($this);

            $this->actingAs($user);
            publicStory('Test Story', $user->id);

            $resp = $api->countAuthoredStories($user->id);
            expect($resp)->toBe(1);
        });
    });
});