<?php

namespace App\Domains\Quote\Private\Controllers;

use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class QuoteProfileController extends Controller
{
    public function __construct(
        private readonly QuotePublicApi $api,
        private readonly ProfilePublicApi $profileApi,
    ) {
    }

    public function show(Request $request, string $profileSlug): JsonResponse
    {
        $profile = $this->profileApi->getPublicProfileBySlug($profileSlug);

        if ($profile === null) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        $viewerId = Auth::check() ? (int) Auth::id() : null;
        $page = max(1, (int) $request->query('page', 1));

        $list = $this->api->getForProfile($profile->user_id, $viewerId, $page);

        return response()->json([
            'items' => array_map(fn($quote) => [
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
            ], $list->items),
            'viewer_is_owner' => $list->viewerIsOwner,
            'can_quote' => $list->canQuote,
            'page' => $list->page,
            'total_count' => $list->totalCount,
        ]);
    }
}
