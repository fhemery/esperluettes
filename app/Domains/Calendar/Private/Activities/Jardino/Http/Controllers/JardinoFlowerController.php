<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers;

use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JardinoFlowerController
{
    public function __construct(
        private readonly JardinoFlowerService $flowerService,
    ) {}

    public function plantFlower(Request $request, int $activityId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'x' => 'required|integer|min:0|max:59',
                'y' => 'required|integer|min:0|max:59',
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
            $validated = $request->validate([
                'x' => 'required|integer|min:0|max:59',
                'y' => 'required|integer|min:0|max:59',
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
}
