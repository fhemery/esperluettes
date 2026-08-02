<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * One row of the *Mes citations* picker: a quote the reader owns, with the
 * reason it cannot be entered, if any.
 *
 * The reader's private note is deliberately absent (assumption A1): it never
 * enters the contest, so it is not in the object a contest template can print.
 */
final class PickerQuoteViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $highlightedText,
        public readonly ?string $storyTitle,
        public readonly ?string $storyUrl,
        public readonly ?string $chapterTitle,
        public readonly ?string $chapterUrl,
        /** One of the QuoteContestSubmissionService::REASON_* keys, or null. */
        public readonly ?string $ineligibilityReason,
    ) {}

    public function isEligible(): bool
    {
        return $this->ineligibilityReason === null;
    }

    /** Lowercased haystack the client-side filter matches against. */
    public function searchable(): string
    {
        return mb_strtolower(trim(implode(' ', array_filter([
            $this->highlightedText,
            $this->storyTitle,
            $this->chapterTitle,
        ]))));
    }
}
