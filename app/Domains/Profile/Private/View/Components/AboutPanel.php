<?php

namespace App\Domains\Profile\Private\View\Components;

use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

/**
 * About tab of a user's profile: bio and social links.
 *
 * Self-hydrating, like every other profile tab: it takes the owner and loads
 * the profile itself rather than being handed a model.
 */
class AboutPanel extends Component
{
    public ?Profile $profile;

    public function __construct(
        public int $ownerUserId,
    ) {
        $this->profile = Profile::query()->where('user_id', $this->ownerUserId)->first();
    }

    public function render(): ViewContract
    {
        return view('profile::components.about-panel');
    }
}
