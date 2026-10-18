<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** Chapitres / Fanarts tabs around the story's chapter list; renders the slot alone when the viewer may not see the feature. */
class StoryTabs extends Component
{
    public bool $visible;

    public function __construct(public int $storyId, public string $storySlug)
    {
        $this->visible = app(FanartPolicy::class)->seeFeature(Auth::id(), $storyId);
    }

    public function render(): View
    {
        return view('fanart::components.story-tabs');
    }
}
