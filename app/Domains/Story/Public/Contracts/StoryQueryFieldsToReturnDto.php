<?php

namespace App\Domains\Story\Public\Contracts;

class StoryQueryFieldsToReturnDto
{
 public function __construct(
        readonly bool $includeAuthors = false,
        readonly bool $includeGenreIds = false,
        readonly bool $includeTriggerWarningIds = false,
        readonly bool $includeChapters = false,
        readonly bool $includeWordCount = false,
        readonly bool $includePublishedChaptersCount = false,
        readonly bool $includeReadingProgress = false,
        readonly bool $includeCollaborators = false,
    ) {

    }
}
