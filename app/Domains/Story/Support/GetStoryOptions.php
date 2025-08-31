<?php

namespace App\Domains\Story\Support;

class GetStoryOptions
{
    public bool $includeAuthors = false;
    public bool $includeGenreIds = false;
    public bool $includeTriggerWarningIds = false;
    public bool $includeChapters = false;

    public function __construct(
        bool $includeAuthors = false,
        bool $includeGenreIds = false,
        bool $includeTriggerWarningIds = false,
        bool $includeChapters = false,
    ) {
        $this->includeAuthors = $includeAuthors;
        $this->includeGenreIds = $includeGenreIds;
        $this->includeTriggerWarningIds = $includeTriggerWarningIds;
        $this->includeChapters = $includeChapters;
    }
}
