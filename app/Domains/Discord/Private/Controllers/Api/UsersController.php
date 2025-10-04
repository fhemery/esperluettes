<?php

namespace App\Domains\Discord\Private\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Discord\Private\Requests\DiscordConnectRequest;
use App\Domains\Discord\Private\Services\DiscordAuthService;

class UsersController extends BaseController
{
    public function __construct(
        private readonly AuthPublicApi $authApi,
        private readonly DiscordAuthService $authService,
    ) {}

    public function connect(DiscordConnectRequest $request): JsonResponse
    {
        // Validation already executed by DiscordConnectRequest
        $validated = $request->validated();

        // Consume code: must exist, not expired, and unused
        $userId = $this->authService->consumeValidCode($validated['code']);
        if ($userId === null) {
            return response()->json([
                'error' => 'Invalid code',
                'message' => 'The provided code is invalid or expired.',
            ], 404);
        }

        // Build roles array (slugs)
        $rolesByUser = $this->authApi->getRolesByUserIds([$userId]);
        $roleDtos = $rolesByUser[$userId] ?? [];
        $roles = array_values(array_map(fn($dto) => $dto->slug, $roleDtos));

        // Link discord user through service; handle conflict
        $discordId = $validated['discordId'];
        $discordUsername = $validated['discordUsername'];
        $linkResult = $this->authService->linkDiscordUser($userId, $discordId, $discordUsername);
        if (($linkResult['success'] ?? false) !== true) {
            $reason = $linkResult['reason'] ?? 'conflict';
            $message = $reason === 'user_already_linked'
                ? 'This user is already linked to a Discord account.'
                : 'This Discord account is already linked to another user.';
            return response()->json([
                'error' => 'Conflict',
                'message' => $message,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'userId' => (int) $userId,
            'roles' => $roles,
        ]);
    }

    public function show(string $discordId): JsonResponse
    {
        $userId = $this->authService->getUserIdByDiscordId($discordId);
        if ($userId === null) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Discord user not found',
            ], 404);
        }
        $rolesByUser = $this->authApi->getRolesByUserIds([$userId]);
        $roleDtos = $rolesByUser[$userId] ?? [];
        $roles = array_values(array_map(fn($dto) => $dto->slug, $roleDtos));

        return response()->json([
            'userId' => $userId,
            'roles'  => $roles,
        ]);
    }
}
