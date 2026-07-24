<?php

namespace App\Domains\Quote\Private\Services;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Public\Events\ChapterPassageQuoted;
use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Private\Support\QuoteNoteSanitizer;
use App\Domains\Quote\Public\Api\Contracts\CreateQuoteDto;
use App\Domains\Quote\Public\Api\Contracts\QuoteDto;
use App\Domains\Quote\Public\Api\Contracts\QuoteListDto;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StoryChapterDto;
use App\Domains\Story\Public\Contracts\StorySummaryDto;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(
        private readonly QuotePolicy $policy,
        private readonly QuoteNoteSanitizer $sanitizer,
        private readonly StoryPublicApi $storyApi,
        private readonly ProfilePublicApi $profileApi,
        private readonly EventBus $eventBus,
    ) {
    }

    public function create(int $chapterId, int $userId, CreateQuoteDto $dto): QuoteDto
    {
        if (!$this->policy->canQuote($dto->storyId, $userId)) {
            abort(403);
        }

        $note = $this->sanitizer->sanitize($dto->note);

        $quote = DB::transaction(function () use ($chapterId, $userId, $dto, $note) {
            return Quote::create([
                'user_id' => $userId,
                'chapter_id' => $chapterId,
                'story_id' => $dto->storyId,
                'highlighted_text' => $dto->highlightedText,
                'prefix' => $dto->prefix,
                'suffix' => $dto->suffix,
                'note' => $note,
            ]);
        });

        $this->eventBus->emit(new ChapterPassageQuoted(
            quoterId: $userId,
            chapterId: $chapterId,
            storyId: $dto->storyId,
            highlightedText: $dto->highlightedText,
        ));

        return $this->toChapterQuoteDto($quote, $userId);
    }

    public function updateNote(int $quoteId, int $userId, ?string $rawNote): QuoteDto
    {
        $quote = Quote::findOrFail($quoteId);

        if (!$this->policy->canEditOrDelete($quote, $userId)) {
            abort(403);
        }

        $note = $this->sanitizer->sanitize($rawNote);

        DB::transaction(function () use ($quote, $note) {
            $quote->update(['note' => $note]);
        });

        return $this->toChapterQuoteDto($quote, $userId);
    }

    public function delete(int $quoteId, int $userId): void
    {
        $quote = Quote::findOrFail($quoteId);

        if (!$this->policy->canEditOrDelete($quote, $userId)) {
            abort(403);
        }

        $quote->delete();
    }

    public function getForChapter(int $chapterId, int $storyId, int $userId): QuoteListDto
    {
        $quotes = Quote::query()
            ->where('chapter_id', $chapterId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $canQuote = $this->policy->canQuote($storyId, $userId);

        $items = $quotes->map(fn($q) => $this->toChapterQuoteDto($q, $userId))->all();

        return new QuoteListDto(
            items: $items,
            viewerIsOwner: true,
            canQuote: $canQuote,
            page: 1,
            totalCount: count($items),
        );
    }

    public function getForProfile(int $profileUserId, ?int $viewerId, int $page): QuoteListDto
    {
        $isOwner = $viewerId !== null && $viewerId === $profileUserId;

        if (!$isOwner && !$this->policy->canViewQuoteBook($profileUserId, $viewerId)) {
            return new QuoteListDto([], false, false, $page, 0);
        }

        $perPage = 20;
        $paginator = Quote::query()
            ->where('user_id', $profileUserId)
            ->orderByDesc('created_at')
            ->paginate($perPage, page: $page);

        $quotes = $paginator->items();

        if (empty($quotes)) {
            return new QuoteListDto([], $isOwner, false, $page, (int) $paginator->total());
        }

        $storyIds = array_values(array_unique(array_map(fn($q) => $q->story_id, $quotes)));
        $chapterIds = array_values(array_unique(array_map(fn($q) => $q->chapter_id, $quotes)));

        $stories = $this->storyApi->getStoriesByIds($storyIds);
        $chapters = $this->storyApi->getChaptersByIds($chapterIds);
        $authorIdsByStory = $this->storyApi->getAuthorIdsByStoryIds($storyIds);

        $allAuthorIds = array_values(array_unique(array_merge(...(array_values($authorIdsByStory) ?: [[]]))));
        $profiles = $allAuthorIds ? $this->profileApi->getPublicProfiles($allAuthorIds) : [];

        $items = [];
        foreach ($quotes as $quote) {
            /** @var StorySummaryDto|null $story */
            $story = $stories[$quote->story_id] ?? null;
            /** @var StoryChapterDto|null $chapter */
            $chapter = $chapters[$quote->chapter_id] ?? null;

            $chapterAvailable = $story !== null
                && $chapter !== null
                && $chapter->status === 'published';

            if (!$isOwner && !$chapterAvailable) {
                continue;
            }

            if (!$isOwner && $story !== null && $viewerId !== null) {
                $accessible = $this->storyApi->filterUsersWithAccessToStory([$viewerId], $quote->story_id);
                if (!in_array($viewerId, $accessible)) {
                    continue;
                }
            }

            $storyUrl = $story ? route('stories.show', ['slug' => $story->slug]) : null;
            $chapterUrl = ($story && $chapter)
                ? route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug])
                : null;

            $authorProfiles = [];
            foreach ($authorIdsByStory[$quote->story_id] ?? [] as $authorId) {
                if (isset($profiles[$authorId])) {
                    $authorProfiles[] = $profiles[$authorId];
                }
            }

            $items[] = new QuoteDto(
                id: (int) $quote->id,
                chapterId: (int) $quote->chapter_id,
                storyId: (int) $quote->story_id,
                highlightedText: $quote->highlighted_text,
                prefix: $quote->prefix,
                suffix: $quote->suffix,
                note: $isOwner ? $quote->note : null,
                storyTitle: $story?->title,
                storyUrl: $storyUrl,
                chapterTitle: $chapter?->title,
                chapterUrl: $chapterUrl,
                authorProfiles: $authorProfiles,
                createdAt: $quote->created_at,
                canEditNote: $isOwner,
                canDelete: $isOwner,
                chapterAvailable: $chapterAvailable,
                anchorMissing: false,
            );
        }

        return new QuoteListDto(
            items: $items,
            viewerIsOwner: $isOwner,
            canQuote: false,
            page: $page,
            totalCount: (int) $paginator->total(),
        );
    }

    private function toChapterQuoteDto(Quote $quote, int $userId): QuoteDto
    {
        return new QuoteDto(
            id: (int) $quote->id,
            chapterId: (int) $quote->chapter_id,
            storyId: (int) $quote->story_id,
            highlightedText: $quote->highlighted_text,
            prefix: $quote->prefix,
            suffix: $quote->suffix,
            note: $quote->note,
            storyTitle: null,
            storyUrl: null,
            chapterTitle: null,
            chapterUrl: null,
            authorProfiles: [],
            createdAt: $quote->created_at,
            canEditNote: true,
            canDelete: true,
            chapterAvailable: true,
            anchorMissing: false,
        );
    }
}
