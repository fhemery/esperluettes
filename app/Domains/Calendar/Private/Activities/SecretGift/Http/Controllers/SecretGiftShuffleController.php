<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers;

use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftConfigService;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\ShuffleService;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Http\RedirectResponse;

/**
 * The admin's single destructive action on a Secret Gift: pairing the
 * participants.
 *
 * The role gate is the route's (`moderator`, `admin`, `tech-admin`); the state
 * gate is here, because `ShuffleService` knows only the ≥2-participants rule.
 */
class SecretGiftShuffleController
{
    public function __construct(
        private readonly ShuffleService $shuffle,
        private readonly SecretGiftConfigService $config,
    ) {}

    public function shuffle(Activity $activity): RedirectResponse
    {
        // A disabled button in the panel is presentation; this is the boundary.
        if (! $this->config->isShuffleAllowedInState($activity)) {
            abort(403);
        }

        try {
            $count = $this->shuffle->performShuffle($activity);
        } catch (\InvalidArgumentException) {
            // Too few participants is an admin mistake, not a server fault.
            return back()->with('error', __('secret-gift::secret-gift.flash.shuffle_not_enough_participants'));
        }

        return back()->with('success', __('secret-gift::secret-gift.flash.shuffled', ['count' => $count]));
    }
}
