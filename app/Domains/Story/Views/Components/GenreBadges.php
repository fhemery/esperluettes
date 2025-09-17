<?php

namespace App\Domains\Story\Views\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GenreBadges extends Component
{
    /** @var array<int,string> */
    public array $genres;

    // Layout configuration (public so they can be overridden via attributes if needed)
    public int $totalWidth;   // px
    public int $gap;          // px
    public int $badgeBase;    // px (base for badge width beyond text)
    public int $fontSize;     // px
    public float $avgCharRatio; // average char width relative to font size
    public int $plusMin;      // px (minimum width for +X badge, excluding gap)

    /** @var array<int,string> */
    public array $shown = [];
    /** @var array<int,string> */
    public array $hidden = [];

    public function __construct(
        array $genres = [],
        int $totalWidth = 250,
        int $gap = 8,
        int $badgeBase = 12,
        int $fontSize = 12,
        float $avgCharRatio = 0.5,
        int $plusMin = 20,
    ) {
        $this->genres = array_values($genres);
        $this->totalWidth = $totalWidth;
        $this->gap = $gap;
        $this->badgeBase = $badgeBase;
        $this->fontSize = $fontSize;
        $this->avgCharRatio = $avgCharRatio;
        $this->plusMin = $plusMin;

        $this->compute();
    }

    private function textWidth(string $text): int
    {
        $len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        return (int) ceil($len * $this->fontSize * $this->avgCharRatio);
    }

    private function compute(): void
    {
        $this->shown = [];
        $this->hidden = [];
        $remainingWidth = $this->totalWidth;

        foreach ($this->genres as $name) {
            $badgeWidth = $this->badgeBase + $this->textWidth($name);

            $placedCount = count($this->shown) + 1;
            $remainingAfterPlace = (count($this->genres) - $placedCount);

            if ($remainingWidth > $badgeWidth + ($remainingAfterPlace > 0 ? $this->gap + $this->plusMin : 0)) {
                $this->shown[] = $name;
                $remainingWidth -= $badgeWidth;
                $remainingWidth -= $this->gap;
            } else {
                $this->hidden[] = $name;
            }
        }
    }

    public function render(): View
    {
        return view('story::components.genre-badges');
    }
}
