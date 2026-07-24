<?php

namespace App\Domains\Quote\Public\Api;

use App\Domains\Quote\Private\Services\QuotePolicy;
use App\Domains\Quote\Private\Services\QuoteService;
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

    public function getForProfile(int $profileUserId, ?int $viewerId, int $page): QuoteListDto
    {
        return $this->service->getForProfile($profileUserId, $viewerId, $page);
    }

    public function canViewQuoteBook(int $profileUserId, ?int $viewerId): bool
    {
        return $this->policy->canViewQuoteBook($profileUserId, $viewerId);
    }
}
