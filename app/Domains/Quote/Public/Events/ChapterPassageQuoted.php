<?php

namespace App\Domains\Quote\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class ChapterPassageQuoted implements DomainEvent
{
    public function __construct(
        public readonly int $quoterId,
        public readonly int $chapterId,
        public readonly int $storyId,
        public readonly string $highlightedText,
    ) {
    }

    public static function name(): string
    {
        return 'Quote.ChapterPassageQuoted';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'quoterId' => $this->quoterId,
            'chapterId' => $this->chapterId,
            'storyId' => $this->storyId,
            'highlightedText' => $this->highlightedText,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            quoterId: (int) ($payload['quoterId'] ?? 0),
            chapterId: (int) ($payload['chapterId'] ?? 0),
            storyId: (int) ($payload['storyId'] ?? 0),
            highlightedText: (string) ($payload['highlightedText'] ?? ''),
        );
    }

    public function summary(): string
    {
        return "Quoter #{$this->quoterId} quoted chapter #{$this->chapterId}";
    }
}
