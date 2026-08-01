<?php

namespace App\Domains\Quote\Private\Services;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Public\Events\ChapterPassageQuoted;
use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Private\Support\QuoteNoteSanitizer;
use App\Domains\Quote\Public\Api\Contracts\AggregateQuoteDto;
use App\Domains\Quote\Public\Api\Contracts\ChapterAggregateDto;
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

    /**
     * Number of live quotes on a chapter. No authorisation: the caller is
     * already behind the chapter-aggregate policy.
     */
    public function countForChapter(int $chapterId): int
    {
        return Quote::query()
            ->where('chapter_id', $chapterId)
            ->count();
    }

    /**
     * The chapter's quotes as their author sees them: no note, ever — neither
     * in the DTO nor in the SELECT.
     */
    public function getChapterAggregate(int $chapterId): ChapterAggregateDto
    {
        $rows = Quote::query()
            ->select(['id', 'user_id', 'highlighted_text', 'prefix', 'suffix', 'created_at'])
            ->where('chapter_id', $chapterId)
            ->orderByDesc('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return new ChapterAggregateDto([], 0);
        }

        $userIds = $rows->pluck('user_id')->filter()->unique()->values()->all();
        $profiles = $userIds ? $this->profileApi->getPublicProfiles($userIds) : [];

        $items = [];
        foreach ($rows as $row) {
            $quoter = $profiles[$row->user_id] ?? null;

            // Defensive: quotes are hard-deleted with their owner, so a row
            // without a resolvable profile should not exist.
            if ($quoter === null) {
                continue;
            }

            $items[] = new AggregateQuoteDto(
                id: (int) $row->id,
                highlightedText: $row->highlighted_text,
                prefix: $row->prefix,
                suffix: $row->suffix,
                createdAt: $row->created_at,
                quoter: $quoter,
            );
        }

        return new ChapterAggregateDto($items, count($items));
    }

    public function getForProfile(int $profileUserId, ?int $viewerId, int $page): QuoteListDto
    {
        $isOwner = $viewerId !== null && $viewerId === $profileUserId;

        if (!$isOwner && !$this->policy->canViewQuoteBook($profileUserId, $viewerId)) {
            return new QuoteListDto([], false, false, $page, 0);
        }

        $perPage = 20;
        $page = max(1, $page);

        if ($isOwner) {
            // The owner sees everything, so we can paginate at the DB level.
            $paginator = Quote::query()
                ->where('user_id', $profileUserId)
                ->orderByDesc('created_at')
                ->paginate($perPage, page: $page);

            $items = $this->buildProfileItems($paginator->items(), true, $viewerId);

            return new QuoteListDto($items, true, false, $page, (int) $paginator->total());
        }

        // Non-owner: visibility filtering must run before pagination so the
        // page slice and total reflect only entries the viewer may actually see
        // (unavailable chapters / inaccessible stories are excluded entirely).
        $allRows = Quote::query()
            ->where('user_id', $profileUserId)
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $visible = $this->filterVisibleForViewer($allRows, $viewerId);

        $total = count($visible);
        $pageRows = array_slice($visible, ($page - 1) * $perPage, $perPage);
        $items = $this->buildProfileItems($pageRows, false, $viewerId);

        return new QuoteListDto($items, false, false, $page, $total);
    }

    /**
     * Keep only the quote rows the viewer is allowed to see: chapter must be
     * available (published) and the viewer must have access to the story.
     * Story access is resolved once per unique story, not once per row.
     *
     * @param array<int, Quote> $rows
     * @return array<int, Quote>
     */
    private function filterVisibleForViewer(array $rows, int $viewerId): array
    {
        if (empty($rows)) {
            return [];
        }

        $storyIds = array_values(array_unique(array_map(fn($q) => $q->story_id, $rows)));
        $chapterIds = array_values(array_unique(array_map(fn($q) => $q->chapter_id, $rows)));

        $stories = $this->storyApi->getStoriesByIds($storyIds);
        $chapters = $this->storyApi->getChaptersByIds($chapterIds);

        $accessByStory = [];
        foreach ($storyIds as $storyId) {
            $accessByStory[$storyId] = in_array(
                $viewerId,
                $this->storyApi->filterUsersWithAccessToStory([$viewerId], $storyId),
                true,
            );
        }

        $visible = [];
        foreach ($rows as $row) {
            /** @var StorySummaryDto|null $story */
            $story = $stories[$row->story_id] ?? null;
            /** @var StoryChapterDto|null $chapter */
            $chapter = $chapters[$row->chapter_id] ?? null;

            $chapterAvailable = $story !== null
                && $chapter !== null
                && $chapter->status === 'published';

            if (!$chapterAvailable) {
                continue;
            }

            if (!($accessByStory[$row->story_id] ?? false)) {
                continue;
            }

            $visible[] = $row;
        }

        return $visible;
    }

    /**
     * Build QuoteDto items for a profile page slice, resolving story/chapter/
     * author metadata in batch. The private note is populated only for the owner.
     *
     * @param array<int, Quote> $rows
     * @return array<int, QuoteDto>
     */
    private function buildProfileItems(array $rows, bool $isOwner, ?int $viewerId): array
    {
        if (empty($rows)) {
            return [];
        }

        $storyIds = array_values(array_unique(array_map(fn($q) => $q->story_id, $rows)));
        $chapterIds = array_values(array_unique(array_map(fn($q) => $q->chapter_id, $rows)));

        $stories = $this->storyApi->getStoriesByIds($storyIds);
        $chapters = $this->storyApi->getChaptersByIds($chapterIds);
        $authorIdsByStory = $this->storyApi->getAuthorIdsByStoryIds($storyIds);

        $allAuthorIds = array_values(array_unique(array_merge(...(array_values($authorIdsByStory) ?: [[]]))));
        $profiles = $allAuthorIds ? $this->profileApi->getPublicProfiles($allAuthorIds) : [];

        $items = [];
        foreach ($rows as $quote) {
            /** @var StorySummaryDto|null $story */
            $story = $stories[$quote->story_id] ?? null;
            /** @var StoryChapterDto|null $chapter */
            $chapter = $chapters[$quote->chapter_id] ?? null;

            $chapterAvailable = $story !== null
                && $chapter !== null
                && $chapter->status === 'published';

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

        return $items;
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
