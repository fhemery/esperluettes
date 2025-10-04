<?php

namespace App\Domains\Discord\Private\Views\Components;

use App\Domains\Discord\Private\Services\DiscordAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class DiscordComponent extends Component
{
    public function __construct(
        private readonly DiscordAuthService $service,
    ) {}

    public function render(): View|string
    {
        $user = Auth::user();
        // Environment gating: if DISCORD_RESTRICTED_ACCESS_USER_IDS is defined and non-empty,
        // only render the component for users whose id is in the list.
        $restricted = (string) (env('DISCORD_RESTRICTED_ACCESS_USER_IDS') ?? '');
        if (trim($restricted) !== '') {
            $allowedIds = array_values(array_filter(array_map(function ($v) {
                $v = trim((string) $v);
                return $v !== '' ? (int) $v : null;
            }, explode(',', $restricted))));

            // If no authenticated user or not in allowed list, render nothing
            $uid = $user ? (int) $user->getAuthIdentifier() : null;
            if (!$uid || !in_array($uid, $allowedIds, true)) {
                return '';
            }
        }

        $isLinked = false;
        $discordUsername = null;

        if ($user) {
            $link = $this->service->getDiscordByUserId((int) $user->getAuthIdentifier());
            if ($link) {
                $isLinked = true;
                $discordUsername = $link->discord_username;
            }
        }

        return view('discord::components.discord', [
            'isLinked' => $isLinked,
            'discordUsername' => $discordUsername,
        ]);
    }
}
