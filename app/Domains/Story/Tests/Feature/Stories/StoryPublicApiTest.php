<?php

use Tests\TestCase;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StorySummaryDto;
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

    describe('isAuthor', function () {
        it('returns false for non-authors', function () {
            $api = app(StoryPublicApi::class);
            $user = alice($this);
            $story = publicStory('Test Story', $user->id);

            $reader = bob($this);
            $resp = $api->isAuthor($reader->id, $story->id);
            expect($resp)->toBe(false);
        });

        it('returns true for authors', function () {
            $api = app(StoryPublicApi::class);
            $user = alice($this);
            $story = publicStory('Test Story', $user->id);

            $resp = $api->isAuthor($user->id, $story->id);
            expect($resp)->toBe(true);
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
            DB::table('story_collaborators')->insert([
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

    describe('GetStory', function () {
        it('returns null for a non existing story', function () {
            $api = app(StoryPublicApi::class);
            $resp = $api->getStory(999999);
            expect($resp)->toBeNull();
        });
        it('returns a story by id', function () {
            $api = app(StoryPublicApi::class);
            $story = publicStory('Test Story', alice($this)->id);
            $resp = $api->getStory($story->id);
            expect($resp)->toBeInstanceOf(StorySummaryDto::class);
            expect($resp->title)->toBe('Test Story');
            expect($resp->slug)->toBe($story->slug);
            expect($resp->visibility)->toBe($story->visibility);
            expect($resp->cover_url)->toBe('');
        });

        it('should sum the chapters of event private stories', function() {
            $api = app(StoryPublicApi::class);
            $alice = alice($this);
            $story = privateStory('Test Story', $alice->id);
            createPublishedChapter($this, $story, $alice, 
            [
                'title' => 'Chapter 1',
                'content' => 'Description One',
            ]);
            createUnpublishedChapter($this, $story, $alice, 
            [
                'title' => 'Chapter 2',
                'content' => 'Description Two',
            ]);
            
            $resp = $api->getStory($story->id);
            expect($resp)->toBeInstanceOf(StorySummaryDto::class);
            expect($resp->word_count)->toBe(4);
        });
    });
});