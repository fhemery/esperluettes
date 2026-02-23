<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Story\Private\Services\StoryService;
use Illuminate\Http\RedirectResponse;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Story\Public\Events\StoryModeratedAsPrivate;
use App\Domains\Story\Public\Events\StorySummaryModerated;
use App\Domains\Story\Public\Events\StoryCoverModerated;

class StoryModerationController
{
    public function __construct(
        private readonly StoryService $service,
        private readonly EventBus $eventBus,
    ) {
    }

    public function makePrivate(string $slug): RedirectResponse
    {
        $changed = $this->service->makePrivate($slug);
        if ($changed) {
            // We need title and id for the event; fetch via service
            $story = $this->service->getStory($slug);
            if ($story) {
                $this->eventBus->emit(new StoryModeratedAsPrivate(storyId: (int)$story->id, title: (string)$story->title));
            }
        }
        return redirect()->route('dashboard')->with('success', __('story::moderation.make_private.success'));
    }

    public function emptySummary(string $slug): RedirectResponse
    {
        $changed = $this->service->emptySummary($slug);
        if ($changed) {
            $story = $this->service->getStory($slug);
            if ($story) {
                $this->eventBus->emit(new StorySummaryModerated(storyId: (int)$story->id, title: (string)$story->title));
            }
        }
        return redirect()->back()->with('success', __('story::moderation.empty_summary.success'));
    }

    public function removeCover(string $slug): RedirectResponse
    {
        $this->service->removeCover($slug);
        return redirect()->back()->with('success', __('story::moderation.remove_cover.success'));
    }
}
