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
