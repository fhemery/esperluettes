<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\Jardino\View\Components\JardinoComponent;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\GardenMapConstants;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\GardenMapViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoViewModel;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Garden Viewing Integration', function () {
    beforeEach(function () {
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
        createGoal($activityId->id, $user->id, $story->id, 10000);

        $this->activity = $activity;
        $this->story = $story;
        $this->user = $user;
    });

    it('should render garden with empty viewmodel', function () {
        $html = renderJardinoComponent($this->activity);

        expect($html)->toContain('data-width="'.GardenMapConstants::DEFAULT_WIDTH.'"')
            ->and($html)->toContain('data-height="'.GardenMapConstants::DEFAULT_HEIGHT.'"')
            ->and($html)->toContain('data-cell-width="'.GardenMapConstants::DEFAULT_CELL_WIDTH.'"')
            ->and($html)->toContain('data-cell-height="'.GardenMapConstants::DEFAULT_CELL_HEIGHT.'"')
            ->and($html)->toContain('garden-grid');
    });

    it('should include garden map in viewmodel', function () {
        $viewModel = getJardinoViewModel($this->activity);

        expect($viewModel)->toBeInstanceOf(JardinoViewModel::class);
        expect($viewModel->gardenMap)->toBeInstanceOf(GardenMapViewModel::class);
        expect($viewModel->gardenMap->width)->toBe(GardenMapConstants::DEFAULT_WIDTH);
        expect($viewModel->gardenMap->height)->toBe(GardenMapConstants::DEFAULT_HEIGHT);
        expect($viewModel->gardenMap->occupiedCells)->toBe([]);
    });

    it('should render garden with occupied cells', function () {
        // Set activity start date to allow flowers
        updateActivityStartDate($this->activity->id, now()->subDays(10));

        // Create some progress first so user can earn flowers
        $this->actingAs($this->user);
        dispatchChapterCreated($this->story->id, 1000);

        // Plant some flowers first
        plantFlower($this, $this->activity->id, 5, 10)->assertOk();
        plantFlower($this, $this->activity->id, 15, 20, '02')->assertOk();

        $viewModel = getJardinoViewModel($this->activity);

        expect($viewModel->gardenMap->getCell(5, 10)->flowerImage)->toBe('01.png');
        expect($viewModel->gardenMap->getCell(15, 20)->flowerImage)->toBe('02.png');
    });

    

    it('should show garden stats in viewmodel', function () {
        // Set activity start date to allow flowers
        updateActivityStartDate($this->activity->id, now()->subDays(10));

        // Create some progress first so user can earn flowers
        $this->actingAs($this->user);
        dispatchChapterCreated($this->story->id, 1000);

        // Plant some flowers first
        plantFlower($this, $this->activity->id, 1, 1)->assertOk();
        plantFlower($this, $this->activity->id, 2, 2)->assertOk();

        $viewModel = getJardinoViewModel($this->activity);

        $nbCells = GardenMapConstants::DEFAULT_WIDTH * GardenMapConstants::DEFAULT_HEIGHT;
        expect($viewModel->gardenMap->occupiedCells)->toHaveCount(2);
        expect($viewModel->gardenMap->getTotalCells())->toBe($nbCells);
        expect($viewModel->gardenMap->getEmptyCells())->toBe($nbCells - 2);
    });
    
    it('should handle multiple users with different profiles', function () {
        // Create another user
        $otherUser = bob($this);
        $secondStory = publicStory("Bob Story", $otherUser->id);
        createGoal($this->activity->id, $otherUser->id, $secondStory->id, 10000);

        // First user earns flowers
        $this->actingAs($this->user);
        dispatchChapterCreated($this->story->id, 1000);

        // Second user earns flowers
        $this->actingAs($otherUser);
        updateActivityStartDate($this->activity->id, now()->subDays(10));
        dispatchChapterCreated($secondStory->id, 1000);

        // Plant flowers for both users
        $this->actingAs($this->user);
        plantFlower($this, $this->activity->id, 5, 10)->assertOk();

        $this->actingAs($otherUser);
        plantFlower($this, $this->activity->id, 15, 20)->assertOk();

        $this->actingAs($this->user);
        $viewModel = getJardinoViewModel($this->activity);

        // Check that cells have correct profile data
        $cell1 = $viewModel->gardenMap->getCell(5, 10);
        $cell2 = $viewModel->gardenMap->getCell(15, 20);

        expect($cell1->userId)->toBe($this->user->id);
        expect($cell2->userId)->toBe($otherUser->id);
        expect($cell1->displayName)->toBe('Alice');
        expect($cell2->displayName)->toBe('Bob');
    });
});
