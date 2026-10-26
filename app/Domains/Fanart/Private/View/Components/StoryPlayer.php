<?php

namespace App\Domains\Fanart\Private\View\Components;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/** The story page's single audio element and the queue feeding the `fanartPlayer` Alpine store. */
class StoryPlayer extends Component
{
    /** @var array<int, array{chapterId: int, artistLabel: ?string, artistUrl: ?string, src: string, markReadUrl: string}> */
    public array $entries = [];

    public function __construct(public int $storyId, public string $storySlug)
    {
        $userId = Auth::id();
        if (!app(FanartPolicy::class)->seeFeature($userId, $storyId)) {
            return;
        }

        foreach (app(FanartService::class)->audioPlaylist($storyId, $userId) as $item) {
            $this->entries[] = [
                'chapterId' => $item['chapterId'],
                'artistLabel' => $item['artistName'] !== null ? __('fanart::ui.player.artist', ['name' => $item['artistName']]) : null,
                'artistUrl' => $item['artistUrl'],
                'src' => route('fanart.file', $item['fanartId']),
                'markReadUrl' => route('chapters.read.mark', [
                    'storySlug' => $storySlug,
                    'chapterSlug' => $item['chapterSlug'],
                ]),
            ];
        }
    }

    public function shouldRender(): bool
    {
        return $this->entries !== [];
    }

    public function render(): View
    {
        return view('fanart::components.story-player');
    }
}
