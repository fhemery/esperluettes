<?php

namespace App\Domains\StoryRef\Public\Contracts;

use Illuminate\Support\Collection;

class StoryRefsDto
{
    /** @param Collection<int,TypeDto> $types
     *  @param Collection<int,GenreDto> $genres
     *  @param Collection<int,AudienceDto> $audiences
     *  @param Collection<int,StatusDto> $statuses
     *  @param Collection<int,TriggerWarningDto> $triggerWarnings
     *  @param Collection<int,FeedbackDto> $feedbacks
     *  @param Collection<int,CopyrightDto> $copyrights
     */
    public function __construct(
        public readonly Collection $types,
        public readonly Collection $genres,
        public readonly Collection $audiences,
        public readonly Collection $statuses,
        public readonly Collection $triggerWarnings,
        public readonly Collection $feedbacks,
        public readonly Collection $copyrights,
    ) {}
}
