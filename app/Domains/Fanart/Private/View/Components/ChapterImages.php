<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartKind;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** View-only image block at the bottom of a chapter page; renders nothing without a visible image. */
class ChapterImages extends Component
{
    public Collection $fanarts;

    public function __construct(public int $storyId, public int $chapterId)
    {
        $this->fanarts = collect();
        $userId = Auth::id();
        if (!app(FanartPolicy::class)->seeFeature($userId, $storyId)) {
            return;
        }

        $group = app(FanartService::class)->group($storyId, $chapterId, $userId);
        $this->fanarts = $group === null
            ? collect()
            : $group['fanarts']->where('kind', FanartKind::Image)->values();
    }

    public function shouldRender(): bool
    {
        return $this->fanarts->isNotEmpty();
    }

    public function render(): View
    {
        return view('fanart::components.chapter-images');
    }
}
