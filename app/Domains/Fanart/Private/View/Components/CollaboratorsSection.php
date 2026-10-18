<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** Fanartistes section of Story's collaborators page; renders nothing unless the viewer may manage them. */
class CollaboratorsSection extends Component
{
    public string $slug = '';

    /** @var array<int, array{user_id:int, display_name:string, slug:string, avatar_url:string}> */
    public array $fanartists = [];

    public bool $visible = false;

    public function __construct(public int $storyId)
    {
        $this->visible = app(FanartPolicy::class)->manageFanartists(Auth::id(), $storyId);
        if (!$this->visible) {
            return;
        }

        $this->slug = app(StoryPublicApi::class)->getStory($storyId)?->slug ?? '';

        $ids = app(FanartistService::class)->listFanartistIds($storyId);
        $profiles = app(ProfilePublicApi::class)->getPublicProfiles($ids);
        foreach ($ids as $id) {
            $profile = $profiles[$id] ?? null;
            $this->fanartists[] = [
                'user_id' => $id,
                'display_name' => (string) ($profile?->display_name ?? ''),
                'slug' => (string) ($profile?->slug ?? ''),
                'avatar_url' => (string) ($profile?->avatar_url ?? ''),
            ];
        }
    }

    public function render(): View
    {
        return view('fanart::components.collaborators-section');
    }
}
