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
    public int $maxCandidates; // max number of badges to try to show

    /** @var array<int,string> */
    public array $shown = [];
    /** @var array<int,string> */
    public array $hidden = [];

    public function __construct(
        array $genres = [],
        int $totalWidth = 250,
        int $gap = 8,
        int $badgeBase = 20,
        int $fontSize = 14,
        float $avgCharRatio = 0.6,
        int $plusMin = 40,
        int $maxCandidates = 3,
    ) {
        $this->genres = array_values($genres);
        $this->totalWidth = $totalWidth;
        $this->gap = $gap;
        $this->badgeBase = $badgeBase;
        $this->fontSize = $fontSize;
        $this->avgCharRatio = $avgCharRatio;
        $this->plusMin = $plusMin;
        $this->maxCandidates = $maxCandidates;

        $this->compute();
    }

    private function textWidth(string $text): int
    {
        $len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        return (int) ceil($len * $this->fontSize * $this->avgCharRatio);
    }

    private function compute(): void
    {
        $candidates = array_slice($this->genres, 0, $this->maxCandidates);
        $rest = array_slice($this->genres, $this->maxCandidates);

        $shown = [];
        $currentWidth = 0;

        foreach ($candidates as $name) {
            $badgeWidth = $this->badgeBase + $this->textWidth($name);
            $withGap = empty($shown) ? $badgeWidth : ($this->gap + $badgeWidth);

            $placedCount = count($shown) + 1;
            $remainingAfterPlace = (count($candidates) - $placedCount) + count($rest);

            if ($remainingAfterPlace > 0) {
                $needForPlus = (empty($shown) && $withGap === $badgeWidth ? 0 : $this->gap) + $this->plusMin;
                if ($currentWidth + $withGap + $needForPlus <= $this->totalWidth) {
                    $shown[] = $name;
                    $currentWidth += $withGap;
                    continue;
                }
                break;
            }

            if ($currentWidth + $withGap <= $this->totalWidth) {
                $shown[] = $name;
                $currentWidth += $withGap;
            } else {
                break;
            }
        }

        $hiddenFromCandidates = array_values(array_diff($candidates, $shown));
        $hidden = array_merge($hiddenFromCandidates, $rest);

        $this->shown = $shown;
        $this->hidden = $hidden;
    }

    public function render(): View
    {
        return view('story::components.genre-badges');
    }
}
