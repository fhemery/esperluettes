<?php

namespace App\Domains\Quote\Private\Controllers;

use App\Domains\Quote\Public\Api\Contracts\AggregateQuoteDto;
use App\Domains\Quote\Public\Api\Contracts\ChapterAggregateDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ChapterAggregateController extends Controller
{
    public function __construct(
        private readonly QuotePublicApi $api,
    ) {
    }

    /**
     * The chapter id is the only thing read from the request: the story is
     * resolved server-side, so there is no story to forge.
     */
    public function show(Request $request): JsonResponse
    {
        $chapterId = (int) $request->query('chapter_id');

        if ($chapterId < 1) {
            return response()->json(['error' => 'chapter_id is required'], 422);
        }

        $userId = (int) Auth::id();

        if (!$this->api->canViewChapterAggregate($chapterId, $userId)) {
            abort(403);
        }

        return response()->json($this->serializeAggregate($this->api->getChapterAggregate($chapterId)));
    }

    private function serializeAggregate(ChapterAggregateDto $aggregate): array
    {
        return [
            'items' => array_map([$this, 'serializeItem'], $aggregate->items),
            'total_count' => $aggregate->totalCount,
        ];
    }

    private function serializeItem(AggregateQuoteDto $item): array
    {
        return [
            'id' => $item->id,
            'highlighted_text' => $item->highlightedText,
            'prefix' => $item->prefix,
            'suffix' => $item->suffix,
            'created_at' => $item->createdAt->format('c'),
            'quoter' => [
                'user_id' => $item->quoter->user_id,
                'display_name' => $item->quoter->display_name,
                'slug' => $item->quoter->slug,
                'avatar_url' => $item->quoter->avatar_url,
            ],
        ];
    }
}
