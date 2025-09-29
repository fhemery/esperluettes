<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\StoryViewModelBuilder;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class KeepReadingComponent extends Component
{
    public ?StorySummaryViewModel $story = null;
    public ?string $nextChapterUrl = null;
    public bool $hasStory = false;
    public ?string $error = null;

    public function __construct(private StoryService $stories,
    private StoryViewModelBuilder $builder)
    {
        $this->hydrate();
    }

    private function hydrate(): void
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                $this->error = __('story::keep-reading.errors.not_authenticated');
                return;
            }

            $storyWithChapter = $this->stories->getKeepReadingContextForUser($userId);
            $this->hasStory = $storyWithChapter !== null;

            if ($storyWithChapter) {
                $this->story = $this->builder->buildStorySummaryItem($storyWithChapter->story);
                $this->nextChapterUrl = route('chapters.show', ['chapterSlug' => $storyWithChapter->nextChapter->slug, 'storySlug' => $storyWithChapter->story->slug]);
            }
        } catch (\Throwable $e) {
            $this->story = null;
            $this->nextChapterUrl = null;
            $this->hasStory = false;
            $this->error = __('story::keep-reading.errors.unavailable');
        }
    }

    public function render(): ViewContract
    {
        return view('story::components.keep-reading', [
            'story' => $this->story,
            'nextChapterUrl' => $this->nextChapterUrl,
            'hasStory' => $this->hasStory,
            'error' => $this->error,
        ]);
    }
}
