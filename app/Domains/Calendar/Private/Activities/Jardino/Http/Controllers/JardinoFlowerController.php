<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService;
use App\Domains\Calendar\Private\Services\ActivityService;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\GardenMapConstants;
use Illuminate\Validation\ValidationException;

class JardinoFlowerController
{
    public function __construct(
        private readonly JardinoFlowerService $flowerService,
        private readonly ActivityService $activityService,
        private readonly AuthPublicApi $authApi
    ) {}

    public function plantFlower(Request $request, int $activityId): JsonResponse
    {
        try {
            // Check if activity is ongoing
            $activity = $this->activityService->findById($activityId);
            if (!$activity || $activity->state !== ActivityState::ACTIVE) {
                throw new \Exception('Activity is not ongoing');
            }

            $validated = $request->validate([
                'x' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_WIDTH -1),
                'y' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_HEIGHT -1),
                'flower_image' => ['required', 'string', 'regex:/^(\d{2})\.png$/'],
            ]);

            $userId = (int) Auth::id();

            $this->flowerService->plantFlower(
                activityId: $activityId,
                userId: $userId,
                x: $validated['x'],
                y: $validated['y'],
                flowerImage: $validated['flower_image']
            );

            return response()->json([
                'success' => true,
                'message' => 'Flower planted successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function removeFlower(Request $request, int $activityId): JsonResponse
    {
        try {
            // Check if activity is ongoing
            $activity = $this->activityService->findById($activityId);
            if (!$activity || $activity->state !== ActivityState::ACTIVE) {
                throw new \Exception('Activity is not ongoing');
            }

            $validated = $request->validate([
                'x' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_WIDTH -1),
                'y' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_HEIGHT -1),
            ]);

            $userId = (int) Auth::id();

            $this->flowerService->removeFlower(
                activityId: $activityId,
                userId: $userId,
                x: $validated['x'],
                y: $validated['y']
            );

            return response()->json([
                'success' => true,
                'message' => 'Flower removed successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function blockCell(Request $request, int $activityId): JsonResponse
    {
        try {
            // Check if activity is in admin block window (visible and not ended)
            $this->assertAdminBlockWindow($activityId);

            // Check if user is admin
            if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new \Exception('Unauthorized: Admin access required');
            }

            $validated = $request->validate([
                'x' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_WIDTH -1),
                'y' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_HEIGHT -1),
            ]);

            $this->flowerService->blockCell(
                activityId: $activityId,
                x: $validated['x'],
                y: $validated['y']
            );

            return response()->json([
                'success' => true,
                'message' => 'Cell blocked successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function unblockCell(Request $request, int $activityId): JsonResponse
    {
        try {
            // Check if activity is in admin block window (visible and not ended)
            $this->assertAdminBlockWindow($activityId);

            // Check if user is admin
            if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new \Exception('Unauthorized: Admin access required');
            }

            $validated = $request->validate([
                'x' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_WIDTH -1),
                'y' => 'required|integer|min:0|max:'.(GardenMapConstants::DEFAULT_HEIGHT -1),
            ]);

            $this->flowerService->unblockCell(
                activityId: $activityId,
                x: $validated['x'],
                y: $validated['y']
            );

            return response()->json([
                'success' => true,
                'message' => 'Cell unblocked successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Allow admin block/unblock from preview start until activity ends.
     * Throws Exception when outside the window.
     */
    private function assertAdminBlockWindow(int $activityId): void
    {
        $activity = $this->activityService->findById($activityId);
        $now = now();

        if (!$activity) {
            throw new \Exception('Activity is not ongoing');
        }

        $previewStarted = $activity->preview_starts_at && $activity->preview_starts_at <= $now;
        $notEnded = (!$activity->active_ends_at || $activity->active_ends_at >= $now);

        if (!($previewStarted && $notEnded)) {
            throw new \Exception('Activity is not ongoing');
        }
    }
}
