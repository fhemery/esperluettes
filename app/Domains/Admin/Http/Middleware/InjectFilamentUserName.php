<?php

namespace App\Domains\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\Shared\Contracts\ProfilePublicApi;

/**
 * InjectFilamentUserName middleware
 *
 * Why (architecture):
 * - The Auth domain no longer stores a public user name; Profile owns display_name.
 * - Filament expects a non-null user name for the authenticated user during panel boot.
 * - To avoid reintroducing an Auth -> Profile dependency, we scope this concern to the Admin domain only.
 * - This middleware injects a transient `name` attribute on the authenticated user for the Admin panel request.
 *
 * How:
 * - Use a shared contract (ProfilePublicApi) if bound.
 * - Fallback to the email local-part or `user-<id>` if no display_name is available.
 */
class InjectFilamentUserName
{
    public function __construct(private readonly ProfilePublicApi $profiles) {}

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && (empty($user->name))) {
            $display = null;
            $dto = $this->profiles->getPublicProfile($user->id);
            $display = $dto?->display_name;

            if (!is_string($display) || $display === '') {
                $email = (string) ($user->email ?? '');
                $local = $email !== '' ? explode('@', $email)[0] : '';
                $display = $local !== '' ? $local : 'user-' . $user->id;
            }

            // Transient attribute for this request only
            $user->name = $display;
            //$user->avatar_url = $dto?->avatar_url ?? '';
        }

        return $next($request);
    }
}
