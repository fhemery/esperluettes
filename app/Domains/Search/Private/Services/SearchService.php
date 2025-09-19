<?php

namespace App\Domains\Search\Private\Services;

use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(
        private readonly StoryPublicApi $stories,
        private readonly ProfilePublicApi $profiles,
    ) {}

    /**
     * Build the combined partial view model.
     */
    public function buildViewModel(string $query, int $storiesPage, int $profilesPage, int $perPage): array
    {
        $q = trim($query);
        if (strlen($q) < 2) {
            return [
                'q' => $q,
                'stories' => ['items' => [], 'total' => 0],
                'profiles' => ['items' => [], 'total' => 0],
                'storiesPage' => 1,
                'profilesPage' => 1,
                'perPage' => $perPage,
            ];
        }

        $viewerId = Auth::check() ? (int) Auth::id() : null;

        $stories = $this->stories->searchStories($q, $viewerId, 25);
        $profiles = $this->profiles->searchPublicProfiles($q, 25);

        // Prepare highlighted view models (keep original arrays for totals/counts)
        $storiesItems = array_values($stories['items']);
        $profilesItems = array_values($profiles['items']);

        $storiesVm = array_map(function ($s) use ($q) {
            // Expecting StorySearchResultDto with properties
            $title = $this->highlight((string) $s->title, $q);
            $authors = is_array($s->authors) ? $s->authors : [];
            return (object) [
                'url' => $s->url,
                'cover_url' => $s->cover_url,
                'title' => $title,
                'authors' => $authors,
            ];
        }, $storiesItems);

        $profilesVm = array_map(function ($p) use ($q) {
            $name = $this->highlight((string) $p->display_name, $q);
            return (object) [
                'url' => $p->url,
                'avatar_url' => $p->avatar_url,
                'display_name' => $name,
            ];
        }, $profilesItems);

        // Client-side pagination: we slice per-page blocks for initial render
        $storiesSlice = array_slice($storiesVm, ($storiesPage - 1) * $perPage, $perPage);
        $profilesSlice = array_slice($profilesVm, ($profilesPage - 1) * $perPage, $perPage);

        return [
            'q' => $q,
            'stories' => ['items' => $storiesVm, 'total' => (int) ($stories['total'] ?? 0)],
            'profiles' => ['items' => $profilesVm, 'total' => (int) ($profiles['total'] ?? 0)],
            'storiesVisible' => $storiesSlice,
            'profilesVisible' => $profilesSlice,
            'storiesPage' => $storiesPage,
            'profilesPage' => $profilesPage,
            'perPage' => $perPage,
        ];
    }

    /**
     * Accent-insensitive, case-insensitive highlighter using <mark>.
     */
    private function highlight(string $text, string $query): string
    {
        $q = trim($query);
        if ($q === '') {
            return e($text);
        }

        // Normalize both strings (fold accents) to compute match positions
        $normText = Str::ascii($text);
        $normQuery = Str::ascii($q);

        // Escape regex special chars in query
        $pattern = '/' . preg_quote($normQuery, '/') . '/i';
        $offsets = [];
        if (preg_match_all($pattern, $normText, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as [$match, $pos]) {
                $offsets[] = [$pos, strlen($match)];
            }
        }
        if (empty($offsets)) {
            return e($text);
        }

        // Build highlighted HTML by slicing original string
        $result = '';
        $cursor = 0;
        foreach ($offsets as [$pos, $len]) {
            $result .= e(mb_substr($text, $cursor, $pos - $cursor));
            $result .= '<mark>' . e(mb_substr($text, $pos, $len)) . '</mark>';
            $cursor = $pos + $len;
        }
        $result .= e(mb_substr($text, $cursor));
        return $result;
    }
}
