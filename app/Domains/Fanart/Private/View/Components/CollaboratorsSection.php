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

    /** @var array<int, array{id:int, display_name:string, slug:string, avatar_url:string}> */
    public array $pendingRequests = [];

    public bool $visible = false;

    public function __construct(public int $storyId)
    {
        $this->visible = app(FanartPolicy::class)->manageFanartists(Auth::id(), $storyId);
        if (!$this->visible) {
            return;
        }

        $this->slug = app(StoryPublicApi::class)->getStory($storyId)?->slug ?? '';

        $service = app(FanartistService::class);
        $ids = $service->listFanartistIds($storyId);
        $requests = $service->pendingRequests($storyId);
        $profiles = app(ProfilePublicApi::class)->getPublicProfiles(
            array_values(array_unique(array_merge($ids, $requests->pluck('user_id')->all())))
        );
        foreach ($ids as $id) {
            $this->fanartists[] = ['user_id' => $id] + $this->card($profiles[$id] ?? null);
        }
        foreach ($requests as $request) {
            $this->pendingRequests[] = ['id' => $request->id] + $this->card($profiles[$request->user_id] ?? null);
        }
    }

    /** @return array{display_name:string, slug:string, avatar_url:string} */
    private function card(?object $profile): array
    {
        return [
            'display_name' => (string) ($profile?->display_name ?? ''),
            'slug' => (string) ($profile?->slug ?? ''),
            'avatar_url' => (string) ($profile?->avatar_url ?? ''),
        ];
    }

    public function render(): View
    {
        return view('fanart::components.collaborators-section');
    }
}
