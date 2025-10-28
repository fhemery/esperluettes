<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers\JardinoFlowerController;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Flower Planting', function () {
    beforeEach(function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create a story
        $story = publicStory('My Story', $user->id);

        // Create active Jardino activity
        $admin = admin($this);
        $this->actingAs($admin);
        $activityId = createActiveJardino($this);

        // Create a goal with progress so user can earn flowers
        $this->actingAs($user);
        $activity = Activity::findOrFail($activityId->id);
        createGoal($activityId->id, $user->id, $story->id, 10000);

        // Add progress
        updateActivityStartDate($activity->id, now()->subDays(10));


        $this->activity = $activity;
        $this->story = $story;
        $this->user = $user;
    });

    describe('Planting a flower', function () {
        it('should plant a flower successfully', function () {
            // Make available flowers
            dispatchChapterCreated($this->story->id, 1000);

            // Plant flower
            $response = plantFlower($this, $this->activity->id, 5, 10, '01');

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData()->success)->toBe(true);

            // Check that flower was planted in database
            $viewModel = getJardinoViewModel($this->activity);
            $plantedFlower = $viewModel->gardenMap->getCell(5, 10);
            expect($plantedFlower)->not->toBeNull();
            expect($plantedFlower->flowerImage)->toBe('01.png');
            expect($plantedFlower->userId)->toBe($this->user->id);
        });

        it('should not plant flower in occupied cell', function () {
            // Make available flowers
            dispatchChapterCreated($this->story->id, 1000);

            // Plant first flower
            plantFlower($this, $this->activity->id, 5, 10, '01');

            // Plant second flower at same spot
            $response = plantFlower($this, $this->activity->id, 5, 10, '02');

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('already occupied');
        });

        it('should not plant flower without available flowers', function () {
            // No flower is available as we dispatched no event 
            $response = plantFlower($this, $this->activity->id, 5, 10, '01');

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('No flowers available');
        });

        it('should not plant flower if activity is not ongoing', function () {
            // Make available flowers
            dispatchChapterCreated($this->story->id, 1000);

            // Update activity to be finished
            updateActivityStartDate($this->activity->id, now()->addDays(1));

            // Plant flower
            $response = plantFlower($this, $this->activity->id, 5, 10, '01');

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Activity is not ongoing');
        });
    });

    describe('Removing a flower', function () {
        it('should remove a flower successfully', function () {
            // Make available flowers
            dispatchChapterCreated($this->story->id, 1000);

            // Plant a flower first
            plantFlower($this, $this->activity->id, 5, 10, '01');

            $response = removeFlower($this, $this->activity->id, 5, 10);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData()->success)->toBe(true);

            $viewModel = getJardinoViewModel($this->activity);
            $removedFlower = $viewModel->gardenMap->getCell(5, 10);
            expect($removedFlower)->toBeNull();
        });

        it('should not remove flower that does not belong to user', function () {
            // Plant flower with different user
            $otherUser = bob($this);
            $this->actingAs($otherUser);
            createGoal($this->activity->id, $otherUser->id, $this->story->id, 10000);
            
            // Give flowers
            dispatchChapterCreated($this->story->id, 1000);

            // Plant flower
            plantFlower($this, $this->activity->id, 5, 10, '01');

            $this->actingAs($this->user);
            $response = removeFlower($this, $this->activity->id, 5, 10);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('does not belong to user');
        });

        it('should not remove flower if activity is not ongoing', function () {
            // Make available flowers
            dispatchChapterCreated($this->story->id, 1000);

            // Plant a flower first
            plantFlower($this, $this->activity->id, 5, 10, '01');

            // Update activity to be finished
            updateActivityStartDate($this->activity->id, now()->addDays(1));

            $response = removeFlower($this, $this->activity->id, 5, 10);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Activity is not ongoing');
        });
    });
});
