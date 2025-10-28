<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domains\Calendar\Private\Models\Activity;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Helper: block a cell
 */
function blockCellReq(TestCase $t, int $activityId, int $x, int $y)
{
    return $t->postJson("/calendar/activities/{$activityId}/jardino/block-cell", [
        'x' => $x,
        'y' => $y,
    ]);
}

/**
 * Helper: unblock a cell
 */
function unblockCellReq(TestCase $t, int $activityId, int $x, int $y)
{
    return $t->postJson("/calendar/activities/{$activityId}/jardino/unblock-cell", [
        'x' => $x,
        'y' => $y,
    ]);
}

describe('Jardino Admin Block/Unblock Cells', function () {
    beforeEach(function () {
        // Base users
        $user = alice($this);
        $this->actingAs($user);

        // Create a story (keeps setup similar to planting tests)
        $story = publicStory('My Story', $user->id);

        // Create active Jardino activity as admin (same pattern as FlowerPlantingTest)
        $admin = admin($this);
        $this->actingAs($admin);
        $activityRef = createActiveJardino($this);

        $activity = Activity::findOrFail($activityRef->id);

        // Switch back to normal user by default
        $this->actingAs($user);

        $this->activity = $activity;
        $this->story = $story;
        $this->user = $user;
        $this->admin = $admin;
    });

    describe('Block cell', function () {
        it('allows an admin to block an empty cell', function () {
            $this->actingAs($this->admin);

            $response = blockCellReq($this, $this->activity->id, 5, 10);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData()->success)->toBe(true);

            $vm = getJardinoViewModel($this->activity);
            $cell = $vm->gardenMap->getCell(5, 10);
            expect($cell)->not->toBeNull();
            expect($cell->type)->toBe('blocked');
        });

        it('fails for non-admin user', function () {
            $this->actingAs($this->user);

            $response = blockCellReq($this, $this->activity->id, 3, 4);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Unauthorized');
        });

        it('fails with invalid coordinates (validation)', function () {
            $this->actingAs($this->admin);

            $response = blockCellReq($this, $this->activity->id, -1, 100);

            expect($response->getStatusCode())->toBe(422);
            // JSON validation error structure
            $data = $response->getData(true);
            expect($data['errors'])->toHaveKeys(['x', 'y']);
        });

        it('works when the activity is not yet started', function (){
            $this->actingAs($this->admin);

            updateActivityVisibilityStartDate($this->activity->id, now()->addDays(-1));
            updateActivityStartDate($this->activity->id, now()->addDays(1));

            $response = blockCellReq($this, $this->activity->id, 6, 6);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData()->success)->toBe(true);

            $vm = getJardinoViewModel($this->activity);
            $cell = $vm->gardenMap->getCell(6, 6);
            expect($cell)->not->toBeNull();
            expect($cell->type)->toBe('blocked');
        });

        it('fails when the activity is already over', function () {
            $this->actingAs($this->admin);

            // Mark activity as not active (start in future)
            updateActivityStartDate($this->activity->id, now()->addDays(-1));
            updateActivityEndDate($this->activity->id, now()->addDays(-2));

            $response = blockCellReq($this, $this->activity->id, 6, 6);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Activity is not ongoing');
        });

        it('fails when cell already occupied or blocked', function () {
            $this->actingAs($this->admin);

            // First block succeeds
            blockCellReq($this, $this->activity->id, 7, 8);
            // Second block same cell fails
            $response = blockCellReq($this, $this->activity->id, 7, 8);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('occupied or blocked');
        });
    });

    describe('Unblock cell', function () {
        it('allows an admin to unblock a blocked cell', function () {
            $this->actingAs($this->admin);

            blockCellReq($this, $this->activity->id, 9, 9);

            $response = unblockCellReq($this, $this->activity->id, 9, 9);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData()->success)->toBe(true);

            $vm = getJardinoViewModel($this->activity);
            $cell = $vm->gardenMap->getCell(9, 9);
            expect($cell)->toBeNull();
        });

        it('fails for non-admin user', function () {
            $this->actingAs($this->admin);
            blockCellReq($this, $this->activity->id, 12, 12);

            $this->actingAs($this->user);
            $response = unblockCellReq($this, $this->activity->id, 12, 12);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Unauthorized');
        });

        it('fails when unblocking a non-blocked cell', function () {
            $this->actingAs($this->admin);

            $response = unblockCellReq($this, $this->activity->id, 2, 2);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Blocked cell not found');
        });

        it('fails with invalid coordinates (validation)', function () {
            $this->actingAs($this->admin);

            $response = unblockCellReq($this, $this->activity->id, 100, -5);

            expect($response->getStatusCode())->toBe(422);
            $data = $response->getData(true);
            expect($data['errors'])->toHaveKeys(['x', 'y']);
        });

        it('fails when the activity is not started', function () {
            $this->actingAs($this->admin);

            blockCellReq($this, $this->activity->id, 15, 15);
            updateActivityVisibilityStartDate($this->activity->id, now()->addDays(2));
            updateActivityStartDate($this->activity->id, now()->addDays(1));

            $response = unblockCellReq($this, $this->activity->id, 15, 15);

            expect($response->getStatusCode())->toBe(400);
            expect($response->getData()->success)->toBe(false);
            expect($response->getData()->message)->toContain('Activity is not ongoing');
        });
    });
});
