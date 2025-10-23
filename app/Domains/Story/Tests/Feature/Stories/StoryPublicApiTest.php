<?php

use Tests\TestCase;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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

    describe('getStoriesForUser', function () {
        it('returns my stories ordered by updated_at desc and includes coauthored by default', function () {
            $api = app(StoryPublicApi::class);

            $alice = alice($this);
            $bob = bob($this);

            // Create two stories for Alice
            $solo = publicStory('Solo Story', $alice->id);
            $coauth = publicStory('Coauthored Story', $alice->id);

            // Add Bob as co-author on coauthored story
            \DB::table('story_collaborators')->insert([
                'story_id' => $coauth->id,
                'user_id' => $bob->id,
                'role' => 'author',
                'invited_by_user_id' => $alice->id,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            // Touch order via updated_at (make coauthored the most recent)
            $solo->update(['updated_at' => now()->subHour()]);
            $coauth->update(['updated_at' => now()]);

            $items = $api->getStoriesForUser($alice->id, excludeCoauthored: false);

            expect($items)->toBeArray()->and(count($items))->toBe(2);
            // Ordered by updated_at desc: first is coauthored
            expect($items[0]->title)->toBe('Coauthored Story');
            expect($items[1]->title)->toBe('Solo Story');
        });

        it('excludes coauthored stories when excludeCoauthored is true', function () {
            $api = app(StoryPublicApi::class);

            $alice = alice($this);
            $bob = bob($this);

            privateStory('Solo Story', $alice->id);
            $coauth = publicStory('Coauthored Story', $alice->id);

            DB::table('story_collaborators')->insert([
                'story_id' => $coauth->id,
                'user_id' => $bob->id,
                'role' => 'author',
                'invited_by_user_id' => $alice->id,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            $items = $api->getStoriesForUser($alice->id, excludeCoauthored: true);
            expect($items)->toBeArray()->and(count($items))->toBe(1);
            expect($items[0]->title)->toBe('Solo Story');
        });
    });
});