<?php

namespace App\Domains\Quote\Public\Api;

use App\Domains\Quote\Private\Services\QuotePolicy;
use App\Domains\Quote\Private\Services\QuoteService;
use App\Domains\Quote\Public\Api\Contracts\ChapterAggregateDto;
use App\Domains\Quote\Public\Api\Contracts\CreateQuoteDto;
use App\Domains\Quote\Public\Api\Contracts\QuoteDto;
use App\Domains\Quote\Public\Api\Contracts\QuoteListDto;

class QuotePublicApi
{
    public function __construct(
        private readonly QuoteService $service,
        private readonly QuotePolicy $policy,
    ) {
    }

    public function getForChapter(int $chapterId, int $storyId, int $userId): QuoteListDto
    {
        return $this->service->getForChapter($chapterId, $storyId, $userId);
    }

    public function create(int $chapterId, int $userId, CreateQuoteDto $dto): QuoteDto
    {
        return $this->service->create($chapterId, $userId, $dto);
    }

    public function updateNote(int $quoteId, int $userId, ?string $note): QuoteDto
    {
        return $this->service->updateNote($quoteId, $userId, $note);
    }

    public function delete(int $quoteId, int $userId): void
    {
        $this->service->delete($quoteId, $userId);
    }

    public function countForChapter(int $chapterId): int
    {
        return $this->service->countForChapter($chapterId);
    }

    public function getChapterAggregate(int $chapterId): ChapterAggregateDto
    {
        return $this->service->getChapterAggregate($chapterId);
    }

    public function canViewChapterAggregate(int $chapterId, int $userId): bool
    {
        return $this->policy->canViewChapterAggregate($chapterId, $userId);
    }

    public function getForProfile(int $profileUserId, ?int $viewerId, int $page): QuoteListDto
    {
        return $this->service->getForProfile($profileUserId, $viewerId, $page);
    }

    /** Every quote this user owns, newest first. No viewer filtering: the owner is the viewer. */
    public function getAllForOwner(int $userId): QuoteListDto
    {
        return $this->service->getAllForOwner($userId);
    }

    /** One quote, only if $userId owns it; null otherwise. */
    public function getOwnedQuote(int $quoteId, int $userId): ?QuoteDto
    {
        return $this->service->getOwnedQuote($quoteId, $userId);
    }

    /**
     * Net change of the quote population per day, derived from the table:
     * +1 on each row's creation day, -1 on the deletion day of each soft-deleted
     * row (quotes of currently deactivated users). Hard-deleted rows (quotes
     * deleted by their reader, quotes of deleted users) do not appear at all.
     *
     * @return array<string, int> Y-m-d => net change, ascending, zero days omitted
     */
    public function getDailyNetChanges(): array
    {
        return $this->service->getDailyNetChanges();
    }

    public function canViewQuoteBook(int $profileUserId, ?int $viewerId): bool
    {
        return $this->policy->canViewQuoteBook($profileUserId, $viewerId);
    }
}
