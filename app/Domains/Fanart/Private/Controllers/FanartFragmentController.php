<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\StoryResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/** Server-rendered HTML fragments, lazy-loaded by the story page. */
class FanartFragmentController
{
    public function __construct(
        private readonly FanartService $service,
        private readonly FanartPolicy $policy,
        private readonly StoryResolver $stories,
    ) {
    }

    public function tab(string $slug): View
    {
        $story = $this->stories->resolve($slug);
        abort_unless($this->policy->seeFeature(Auth::id(), $story->id), 403);

        return view('fanart::fragments.tab', [
            'story' => $story,
            'summary' => $this->service->tabSummary($story->id, Auth::id()),
        ]);
    }

    public function group(string $slug, string $group): View
    {
        $story = $this->stories->resolve($slug);
        abort_unless($this->policy->seeFeature(Auth::id(), $story->id), 403);

        $data = $this->service->group($story->id, $group === 'work' ? null : (int) $group, Auth::id());
        abort_if($data === null, 404);

        return view('fanart::fragments.group', $data);
    }

    public function show(Fanart $fanart): View
    {
        $userId = Auth::id();
        abort_unless($this->policy->view($userId, $fanart), 403);

        return view('fanart::fragments.viewer', [
            'fanart' => $fanart,
            'details' => $this->service->viewerDetails($fanart),
            'canReview' => $this->policy->review($userId, $fanart),
            'canDelete' => $this->policy->delete($userId, $fanart),
            'canModerate' => $this->policy->moderate($userId, $fanart),
            'canReport' => $fanart->user_id !== $userId,
        ]);
    }
}
