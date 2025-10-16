<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Story\Private\Services\ChapterService;
use Illuminate\Http\RedirectResponse;

class ChapterModerationController
{
    public function __construct(
        private ChapterService $service,
    ) {}

    public function unpublish(string $slug): RedirectResponse
    {
        $story = $this->service->unpublishBySlug($slug);
        return redirect()->to(route('stories.show', ['slug' => $story->slug]))
            ->with('success', trans('story::moderation.unpublish.success'));
    }

    public function emptyContent(string $slug): RedirectResponse
    {
        $this->service->emptyContentBySlug($slug);

        return redirect()->back()
            ->with('success', trans('story::moderation.empty_chapter_content.success'));
    }
}
