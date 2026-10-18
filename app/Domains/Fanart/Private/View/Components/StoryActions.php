<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Support\FanartKind;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** « Proposer un fanart » / « Ajouter un artwork » / role request button, whichever applies to the viewer. */
class StoryActions extends Component
{
    public bool $canSubmit = false;

    public bool $canArtwork = false;

    public bool $visible;

    public function __construct(public int $storyId, public string $storySlug)
    {
        $userId = Auth::id();
        $policy = app(FanartPolicy::class);

        $this->visible = $policy->seeFeature($userId, $storyId);
        if ($this->visible) {
            $this->canArtwork = $policy->uploadArtwork($userId, $storyId);
            $this->canSubmit = $policy->submit($userId, $storyId, FanartKind::Image)
                || $policy->submit($userId, $storyId, FanartKind::Audio);
        }
    }

    public function render(): View
    {
        return view('fanart::components.story-actions');
    }
}
