<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;

class StoryDto
{
    /**
     * @var StoryRefGenre[] $genres
     * @var StoryRefTriggerWarning[] $triggerWarnings
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $twDisclosure,
        public ?string $summary,
        public ?string $description = null,
        public ?array $genres = null,
        public ?array $triggerWarnings = null,
    ) {
    }

    public static function fromModel(Story $story, StoryQueryFieldsToReturnDto $fieldsToReturn, StoryRefLookupService $refService): self
    {
        $dto = new self(
            id: (int) $story->id,
            title: (string) $story->title,
            twDisclosure: (string) ($story->twDisclosure ?? ''),
            summary: $story->description,
            description: $story->description,
        );

        if ($fieldsToReturn->includeGenreIds) {
            $genreIds = $story->genres->pluck('id')->map(fn($v) => (int) $v)->all();
            $genres = $refService->getGenres();
            $dto->genres = $genres->filter(fn($g) => in_array((int) $g['id'], $genreIds, true))->values()->all();
        }
        if ($fieldsToReturn->includeTriggerWarningIds) {
            $twIds = $story->triggerWarnings->pluck('id')->map(fn($v) => (int) $v)->all();
            $triggerWarnings = $refService->getTriggerWarnings();
            $dto->triggerWarnings = $triggerWarnings->filter(fn($tw) => in_array((int) $tw['id'], $twIds, true))->values()->all();
        }

        return $dto;
    }
}
