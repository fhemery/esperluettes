<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Services\FanartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Fanarts tab of a user's profile: Images / Sons sections, one lazy group per story.
 *
 * Self-hydrating, like Quote's tab: the profile page hands it only the owner.
 */
class ProfileTab extends Component
{
    /** @var array<string, array<int, array{storyId: int, title: string, slug: string, count: int}>> */
    public array $sections;

    public function __construct(FanartService $service, public int $ownerUserId)
    {
        $this->sections = $service->profileStories($ownerUserId, (int) Auth::id());
    }

    public function render(): View
    {
        return view('fanart::components.profile-tab');
    }
}
