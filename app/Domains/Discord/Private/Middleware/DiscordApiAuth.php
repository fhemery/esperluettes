<?php

namespace App\Domains\Discord\Private\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscordApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $apiKey = (string) env('DISCORD_BOT_API_KEY', '');

        if (!$this->isValidBearer($authHeader, $apiKey)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ], 401);
        }

        return $next($request);
    }

    private function isValidBearer(?string $authHeader, string $expected): bool
    {
        if (empty($expected)) {
            // If no expected key configured, deny all
            return false;
        }
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return false;
        }
        $provided = trim(substr($authHeader, 7));
        return hash_equals($expected, $provided);
    }
}
