<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\ModeService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartMode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * One button per kind the viewer may submit (« Proposer une image » / « Proposer un son », or the
 * « Ajouter un artwork » variants for an author), then the role request button, whichever applies.
 */
class StoryActions extends Component
{
    /** @var array<string, string> Kind value => label translation key, only the kinds the viewer may add. */
    public array $kinds = [];

    public ?string $requestLabel = null;

    public bool $visible;

    public function __construct(public int $storyId, public string $storySlug)
    {
        $userId = Auth::id();
        $policy = app(FanartPolicy::class);

        $this->visible = $policy->seeFeature($userId, $storyId);
        if (!$this->visible) {
            return;
        }

        $isArtwork = $policy->uploadArtwork($userId, $storyId);
        foreach (FanartKind::cases() as $kind) {
            if ($isArtwork || $policy->submit($userId, $storyId, $kind)) {
                $this->kinds[$kind->value] = 'fanart::ui.actions.' . ($isArtwork ? 'artwork_' : 'submit_') . $kind->value;
            }
        }

        // The role is per story: when both kinds are reserved, one generic request; otherwise name the closed kind.
        $modes = app(ModeService::class);
        $reserved = array_values(array_filter(
            FanartKind::cases(),
            fn (FanartKind $kind) => $modes->effectiveMode($storyId, $kind) === FanartMode::Designated,
        ));
        $this->requestLabel = count($reserved) === 1 ? 'request_' . $reserved[0]->value : null;
    }

    public function render(): View
    {
        return view('fanart::components.story-actions');
    }
}
