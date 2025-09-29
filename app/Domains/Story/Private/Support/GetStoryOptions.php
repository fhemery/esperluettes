<?php

namespace App\Domains\Story\Private\Support;

class GetStoryOptions
{
    public function __construct(
        readonly bool $includeAuthors = false,
        readonly bool $includeGenreIds = false,
        readonly bool $includeTriggerWarningIds = false,
        readonly bool $includeChapters = false,
        readonly bool $includeWordCount = false,
        readonly bool $includePublishedChaptersCount = false,
        readonly bool $includeReadingProgress = false,
    ) {

    }

    public static function ForCardDisplay(): self
    {
        return new self(
            includeAuthors: true,
            includeGenreIds: true,
            includeTriggerWarningIds: true,
            includeChapters: false,
            includeWordCount: true,
            includePublishedChaptersCount: true,
            includeReadingProgress: false,
        );
    }

    public static function Full(): self
    {
        return new self(
            includeAuthors: true,
            includeGenreIds: true,
            includeTriggerWarningIds: true,
            includeChapters: true,
            includeWordCount: true,
            includePublishedChaptersCount: true,
            includeReadingProgress: true,
        );
    }
}
