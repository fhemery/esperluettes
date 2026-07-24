<?php

namespace App\Domains\Quote\Private\Controllers;

use App\Domains\Quote\Private\Requests\CreateQuoteRequest;
use App\Domains\Quote\Private\Requests\UpdateQuoteNoteRequest;
use App\Domains\Quote\Public\Api\Contracts\CreateQuoteDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuotePublicApi $api,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $chapterId = (int) $request->query('chapter_id');
        $storyId = (int) $request->query('story_id');

        if ($chapterId < 1 || $storyId < 1) {
            return response()->json(['error' => 'chapter_id and story_id are required'], 422);
        }

        $userId = (int) Auth::id();
        $list = $this->api->getForChapter($chapterId, $storyId, $userId);

        return response()->json($this->serializeList($list));
    }

    public function store(CreateQuoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = (int) Auth::id();

        $dto = new CreateQuoteDto(
            storyId: (int) $data['story_id'],
            highlightedText: $data['highlighted_text'],
            prefix: $data['prefix'] ?? null,
            suffix: $data['suffix'] ?? null,
            note: $data['note'] ?? null,
        );

        $quote = $this->api->create((int) $data['chapter_id'], $userId, $dto);

        return response()->json($this->serializeQuote($quote), 201);
    }

    public function updateNote(UpdateQuoteNoteRequest $request, int $quoteId): JsonResponse
    {
        $data = $request->validated();
        $userId = (int) Auth::id();

        $quote = $this->api->updateNote($quoteId, $userId, $data['note'] ?? null);

        return response()->json($this->serializeQuote($quote));
    }

    public function destroy(int $quoteId): Response
    {
        $userId = (int) Auth::id();
        $this->api->delete($quoteId, $userId);

        return response()->noContent();
    }

    private function serializeList(object $list): array
    {
        return [
            'items' => array_map([$this, 'serializeQuote'], $list->items),
            'viewer_is_owner' => $list->viewerIsOwner,
            'can_quote' => $list->canQuote,
            'page' => $list->page,
            'total_count' => $list->totalCount,
        ];
    }

    private function serializeQuote(object $quote): array
    {
        return [
            'id' => $quote->id,
            'chapter_id' => $quote->chapterId,
            'story_id' => $quote->storyId,
            'highlighted_text' => $quote->highlightedText,
            'prefix' => $quote->prefix,
            'suffix' => $quote->suffix,
            'note' => $quote->note,
            'story_title' => $quote->storyTitle,
            'story_url' => $quote->storyUrl,
            'chapter_title' => $quote->chapterTitle,
            'chapter_url' => $quote->chapterUrl,
            'author_profiles' => array_map(fn($p) => [
                'user_id' => $p->user_id,
                'display_name' => $p->display_name,
                'slug' => $p->slug,
                'avatar_url' => $p->avatar_url,
            ], $quote->authorProfiles),
            'created_at' => $quote->createdAt->format('c'),
            'can_edit_note' => $quote->canEditNote,
            'can_delete' => $quote->canDelete,
            'chapter_available' => $quote->chapterAvailable,
            'anchor_missing' => $quote->anchorMissing,
        ];
    }
}
