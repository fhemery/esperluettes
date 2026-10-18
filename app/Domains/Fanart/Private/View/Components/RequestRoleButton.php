<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** « Demander le rôle de fanartiste » / « Demande envoyée — retirer » / nothing. */
class RequestRoleButton extends Component
{
    public string $slug = '';

    public ?int $pendingId = null;

    public bool $canRequest = false;

    public function __construct(public int $storyId)
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        $this->pendingId = app(FanartistService::class)->pendingRequest($userId, $storyId)?->id;
        $this->canRequest = app(FanartPolicy::class)->requestRole($userId, $storyId);

        if ($this->pendingId !== null || $this->canRequest) {
            $this->slug = app(StoryPublicApi::class)->getStory($storyId)?->slug ?? '';
        }
    }

    public function render(): View
    {
        return view('fanart::components.request-role-button');
    }
}
