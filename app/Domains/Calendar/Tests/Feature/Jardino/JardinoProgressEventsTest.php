<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Jardino Progress Updates', function () {
    beforeEach(function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);
    });

    beforeEach(function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create a story for the user
        $story = publicStory('My JardiNo Story', $user->id);
        $this->story = $story;

        // Create active Jardino activity
        $admin = admin($this);
        $this->actingAs($admin);
        $activityId = createActiveJardino($this);

        // Create a goal for the user
        $this->actingAs($user);
        $activity = Activity::findOrFail($activityId->id);
        $this->activity = $activity;

        $goalService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoGoalService::class);
        $this->goal = $goalService->createOrUpdateGoal($activityId->id, $user->id, $story->id, 10000);
    });

    it('should initially have no words written', function() {
        $objective = getJardinoViewModel($this->activity);
        expect($objective)->not->toBeNull();
        expect($objective->wordsWritten)->toBe(0);
        expect($objective->progressPercentage)->toBe(0.0);
        expect($objective->flowersEarned)->toBe(0);
        expect($objective->flowersPlanted)->toBe(0);
        expect($objective->flowersAvailable)->toBe(0);
    });

    describe('ChapterCreated Event', function () {
        it('should add words and update progress when a new chapter is created', function () {
            dispatchChapterCreated($this->story->id, 1500);

            // Check that progress is updated
            updateActivityStartDate($this->activity->id, now()->subDays(10));
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(1500);
            expect($objective->progressPercentage)->toBe(15.0); // 1500 / 10000 * 100
            expect($objective->flowersEarned)->toBe(3); // floor(15/5) = 3
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(3);

            // Create another chapter with more words
            dispatchChapterCreated($this->story->id, 2500, ['id' => 2]);

            // Check that total is updated
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(4000); // 1500 + 2500
            expect($objective->progressPercentage)->toBe(40.0); // 4000 / 10000 * 100
            expect($objective->flowersEarned)->toBe(8); // floor(40/5) = 8
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(8);
        });

        it('should only update progress for active goals of active activities', function () {
            $user = alice($this);
            $this->actingAs($user);

            // Create a story
            $story = publicStory('My Story', $user->id);

            // Create active Jardino activity
            $admin = admin($this);
            $this->actingAs($admin);
            $activityId = createActiveJardino($this);

            // Create a goal
            $this->actingAs($user);
            $activity = Activity::findOrFail($activityId->id);
            $goalService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoGoalService::class);
            $goal = $goalService->createOrUpdateGoal($activityId->id, $user->id, $story->id, 10000);

            // Initially no words
            $objective = getJardinoViewModel($activity);
            expect($objective->wordsWritten)->toBe(0);
            expect($objective->flowersEarned)->toBe(0);
            expect($objective->flowersAvailable)->toBe(0);

            // Create chapter for a different story (should not affect progress)
            $otherStory = publicStory('Other Story', $user->id);
            dispatchChapterCreated($otherStory->id, 2000);

            // Progress should still be 0
            $objective = getJardinoViewModel($activity);
            expect($objective->wordsWritten)->toBe(0);
            expect($objective->flowersEarned)->toBe(0);
            expect($objective->flowersAvailable)->toBe(0);
        });

        it('should never grand more than 2 flowers a day', function () {
            dispatchChapterCreated($this->story->id, 10000);
            
            updateActivityStartDate($this->activity->id, now());
            
            // Progress should still be 0
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(10000);
            expect($objective->flowersEarned)->toBe(2);
            expect($objective->flowersAvailable)->toBe(2);
        });

        it('should never grant more than 25 flowers', function () {
            dispatchChapterCreated($this->story->id, 100000);
            
            updateActivityStartDate($this->activity->id, now()->subDays(20));
            
            // Progress should still be 0
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(100000);
            expect($objective->flowersEarned)->toBe(25);
            expect($objective->flowersAvailable)->toBe(25);
        });
    });

    describe('ChapterUpdated Event', function () {
        it('should add words when chapter is updated with more words', function () {
            dispatchChapterUpdated($this->story->id, 500, 1200);

            // Check that progress is updated (added 700 words: 1200 - 500)
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(700);
            expect(round($objective->progressPercentage))->toBe(7.0); // 700 / 10000 * 100
            expect($objective->flowersEarned)->toBe(1); // floor(7/5) = 1
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(1);
        });

        it('should update biggest count when chapter word count increases beyond previous maximum', function () {
            dispatchChapterUpdated($this->story->id, 0, 1000);

            // Check progress shows 1000 words
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(1000);
            expect($objective->progressPercentage)->toBe(10.0);
            expect($objective->flowersEarned)->toBe(2); // floor(10/5) = 2
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(2);

            // Second update: increase beyond current
            dispatchChapterUpdated($this->story->id, 1000, 1500);

            // Check progress shows 1500 words (biggest count updated)
            updateActivityStartDate($this->activity->id, now()->subDays(10));
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(1500);
            expect($objective->progressPercentage)->toBe(15.0);
            expect($objective->flowersEarned)->toBe(3); // floor(15/5) = 3
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(3);
        });

        it('should keep biggest count when chapter word count decreases', function () {
            dispatchChapterUpdated($this->story->id, 0, 2000);

            // Check progress shows 2000 words
            updateActivityStartDate($this->activity->id, now()->subDays(10));
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(2000);
            expect($objective->progressPercentage)->toBe(20.0);
            expect($objective->flowersEarned)->toBe(4); // floor(20/5) = 4
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(4);

            // Second: decrease word count (biggest count should remain)
            dispatchChapterUpdated($this->story->id, 2000, 800);

            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(800); // Current count
            expect($objective->progressPercentage)->toBe(8.0);
            expect($objective->flowersEarned)->toBe(4); // We already had 4 flowers
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(4);
        });

        it('should subtract words when chapter word count decreases', function () {
            dispatchChapterUpdated($this->story->id, 0, 1000);
            dispatchChapterUpdated($this->story->id, 1000, 600);

            // Check progress shows 600 words (current count, not biggest)
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(600); // Current count, not biggest
            expect($objective->progressPercentage)->toBe(6.0);
            expect($objective->flowersEarned)->toBe(2); // We already have 2 flowers
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(2);
        });
    });

    describe('ChapterDeleted Event', function () {
        it('should remove words when chapter is deleted', function () {
            dispatchChapterCreated($this->story->id, 1500);

            // Now delete the chapter
            dispatchChapterDeleted($this->story->id, 1500);

            // Check progress shows 0 words (1500 removed)
            updateActivityStartDate($this->activity->id, now()->subDays(10));
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(0); // Words removed
            expect($objective->progressPercentage)->toBe(0.0);
            expect($objective->flowersEarned)->toBe(3);
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(3);
        });

        it('should keep biggest count and thus flower count when chapter is deleted', function () {
            dispatchChapterCreated($this->story->id, 3000);// Create chapter with high word count (establish biggest count)

            // Check progress shows 3000 words
            updateActivityStartDate($this->activity->id, now()->subDays(10));
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(3000);
            expect($objective->progressPercentage)->toBe(30.0);
            expect($objective->flowersEarned)->toBe(6); // floor(30/5) = 6
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(6);

            // Now delete the chapter
            dispatchChapterDeleted($this->story->id, 3000);

            // Check progress shows 0 words (current count, but biggest count preserved in snapshot)
            $objective = getJardinoViewModel($this->activity);
            expect($objective->wordsWritten)->toBe(0); // Current count is 0 after deletion
            expect($objective->progressPercentage)->toBe(0.0);
            expect($objective->flowersEarned)->toBe(6);
            expect($objective->flowersPlanted)->toBe(0);
            expect($objective->flowersAvailable)->toBe(6);
        });
    });
});
