<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** Speaker button on a story-page chapter row; renders nothing when the chapter has no audio the viewer may hear. */
class ChapterAudioIcon extends Component
{
    public bool $visible = false;

    public function __construct(public int $storyId, public int $chapterId)
    {
        $userId = Auth::id();
        if (app(FanartPolicy::class)->seeFeature($userId, $storyId)) {
            $playlist = app(FanartService::class)->audioPlaylist($storyId, $userId);
            $this->visible = in_array($chapterId, array_column($playlist, 'chapterId'), true);
        }
    }

    public function render(): View
    {
        return view('fanart::components.chapter-audio-icon');
    }
}
