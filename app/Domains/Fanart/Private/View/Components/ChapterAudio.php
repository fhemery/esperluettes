<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** Native audio player of a chapter page; renders nothing without a published audio the viewer may hear. */
class ChapterAudio extends Component
{
    public ?int $fanartId = null;

    public bool $canReport = false;

    public ?string $artistName = null;

    public ?string $artistUrl = null;

    public function __construct(public int $storyId, public int $chapterId)
    {
        $userId = Auth::id();
        if (!app(FanartPolicy::class)->seeFeature($userId, $storyId)) {
            return;
        }

        $service = app(FanartService::class);
        foreach ($service->audioPlaylist($storyId, $userId) as $item) {
            if ($item['chapterId'] === $chapterId) {
                $this->fanartId = $item['fanartId'];
                $this->artistName = $item['artistName'];
                $this->artistUrl = $item['artistUrl'];
                $fanart = $service->find($this->fanartId);
                $this->canReport = $fanart !== null && $fanart->user_id !== $userId;
            }
        }
    }

    public function shouldRender(): bool
    {
        return $this->fanartId !== null;
    }

    public function render(): View
    {
        return view('fanart::components.chapter-audio');
    }
}
