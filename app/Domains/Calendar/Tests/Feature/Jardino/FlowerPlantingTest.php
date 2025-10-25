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
        $goalService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoGoalService::class);
        $this->goal = $goalService->createOrUpdateGoal($activityId->id, $user->id, $story->id, 10000);

        // Add progress
        updateActivityStartDate($activity->id, now()->subDays(10));
        $snapshot = \App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoStorySnapshot::create([
            'goal_id' => $this->goal->id,
            'story_id' => $story->id,
            'story_title' => $story->title,
            'initial_word_count' => 0,
            'current_word_count' => 1000,
            'biggest_word_count' => 1000,
            'selected_at' => now(),
        ]);

        $this->activity = $activity;
        $this->story = $story;
        $this->user = $user;
    });

    it('should plant a flower successfully', function () {
        // Debug: Check what's in the database
        $goals = \App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal::all();
        expect($goals)->toHaveCount(1);
        expect($goals->first()->activity_id)->toBe($this->activity->id);
        expect($goals->first()->user_id)->toBe($this->user->id);

        $controller = new JardinoFlowerController(
            app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService::class)
        );

        $response = $controller->plantFlower(
            request: request()->merge([
                'x' => 5,
                'y' => 10,
                'flower_image' => '01.png'
            ]),
            activityId: $this->activity->id
        );

        expect($response->getStatusCode())->toBe(200);
        expect($response->getData()->success)->toBe(true);

        // Check that flower was planted in database
        $plantedFlower = \App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGardenCell::query()
            ->where('activity_id', $this->activity->id)
            ->where('x', 5)
            ->where('y', 10)
            ->where('user_id', $this->user->id)
            ->where('type', 'flower')
            ->where('flower_image', '01.png')
            ->first();

        expect($plantedFlower)->not->toBeNull();
    });

    it('should not plant flower in occupied cell', function () {
        // Plant first flower
        $controller = new JardinoFlowerController(
            app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService::class)
        );

        $controller->plantFlower(
            request: request()->merge([
                'x' => 5,
                'y' => 10,
                'flower_image' => '01.png'
            ]),
            activityId: $this->activity->id
        );

        // Try to plant second flower in same cell
        $response = $controller->plantFlower(
            request: request()->merge([
                'x' => 5,
                'y' => 10,
                'flower_image' => '02.png'
            ]),
            activityId: $this->activity->id
        );

        expect($response->getStatusCode())->toBe(400);
        expect($response->getData()->success)->toBe(false);
        expect($response->getData()->message)->toContain('already occupied');
    });

    it('should not plant flower without available flowers', function () {
        // Remove progress so no flowers are available
        $this->goal->storySnapshots()->delete();

        // Debug: Check flower stats
        $flowerService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService::class);
        $flowerStats = $flowerService->calculateAvailableFlowers($this->goal);
        expect($flowerStats['available'])->toBe(0);

        $controller = new JardinoFlowerController($flowerService);

        $response = $controller->plantFlower(
            request: request()->merge([
                'x' => 5,
                'y' => 10,
                'flower_image' => '01.png'
            ]),
            activityId: $this->activity->id
        );

        expect($response->getStatusCode())->toBe(400);
        expect($response->getData()->success)->toBe(false);
        expect($response->getData()->message)->toContain('No flowers available');
    });

    it('should remove a flower successfully', function () {
        // Plant a flower first
        $flowerService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService::class);
        $flowerService->plantFlower($this->activity->id, $this->user->id, 5, 10, '01.png');

        $controller = new JardinoFlowerController($flowerService);

        $response = $controller->removeFlower(
            request: request()->merge([
                'x' => 5,
                'y' => 10
            ]),
            activityId: $this->activity->id
        );

        expect($response->getStatusCode())->toBe(200);
        expect($response->getData()->success)->toBe(true);

        // Check that flower was removed from database
        $removedFlower = \App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGardenCell::query()
            ->where('activity_id', $this->activity->id)
            ->where('x', 5)
            ->where('y', 10)
            ->where('user_id', $this->user->id)
            ->first();

        expect($removedFlower)->toBeNull();
    });

    it('should not remove flower that does not belong to user', function () {
        // Plant flower with different user
        $otherUser = bob($this);
        $goalService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoGoalService::class);
        $otherGoal = $goalService->createOrUpdateGoal($this->activity->id, $otherUser->id, $this->story->id, 10000);

        // Add progress for other user
        updateActivityStartDate($this->activity->id, now()->subDays(10));
        $snapshot = \App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoStorySnapshot::create([
            'goal_id' => $otherGoal->id,
            'story_id' => $this->story->id,
            'story_title' => $this->story->title,
            'initial_word_count' => 0,
            'current_word_count' => 1000,
            'biggest_word_count' => 1000,
            'selected_at' => now(),
        ]);

        $flowerService = app(\App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService::class);
        $flowerService->plantFlower($this->activity->id, $otherUser->id, 5, 10, '01.png');

        $controller = new JardinoFlowerController($flowerService);

        $response = $controller->removeFlower(
            request: request()->merge([
                'x' => 5,
                'y' => 10
            ]),
            activityId: $this->activity->id
        );

        expect($response->getStatusCode())->toBe(400);
        expect($response->getData()->success)->toBe(false);
        expect($response->getData()->message)->toContain('does not belong to user');
    });
});
