<?php

namespace App\Domains\Discord\Private\Controllers\Web;

use App\Domains\Discord\Private\Services\DiscordAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;

class CodeController extends BaseController
{
    public function __construct(private readonly DiscordAuthService $authService)
    {
    }

    public function generate(): JsonResponse
    {
        $userId = Auth::id();
        // Invariant: route uses 'auth' middleware, so user is authenticated
        $code = $this->authService->generateConnectionCodeForUser((int) $userId);

        return response()->json([
            'code' => $code,
        ]);
    }
}
