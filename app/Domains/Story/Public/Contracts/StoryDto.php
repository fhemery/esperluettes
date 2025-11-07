<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Public\Api\StoryMapperHelper;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;

class StoryDto
{
    /**
     * @var StoryRefGenre[] $genres
     * @var StoryRefTriggerWarning[] $triggerWarnings
     * @var ProfileDto[] $authors
     * @var StoryChapterDto[] $chapters
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $twDisclosure,
        public ?string $summary,
        public ?string $description = null,
        public ?array $genres = null,
        public ?array $triggerWarnings = null,
        public ?array $authors = null,
        public ?array $chapters = null,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $lastChapterPublishedAt = null,
    ) {
    }

    public static function fromModel(Story $story, StoryQueryFieldsToReturnDto $fieldsToReturn, StoryMapperHelper $helper): self
    {
        $dto = new self(
            id: (int) $story->id,
            title: (string) $story->title,
            slug: (string) $story->slug,
            twDisclosure: (string) ($story->tw_disclosure ?? ''),
            summary: $story->description,
            description: $story->description,
            createdAt: $story->created_at,
            lastChapterPublishedAt: $story->last_chapter_published_at,
        );

        if ($fieldsToReturn->includeGenreIds) {
            $genreIds = $story->genres->pluck('id')->map(fn($v) => (int) $v)->all();
            $dto->genres = collect($helper->genres)->filter(fn($g) => in_array((int) $g['id'], $genreIds, true))->values()->all();
        }
        if ($fieldsToReturn->includeTriggerWarningIds) {
            $twIds = $story->triggerWarnings->pluck('id')->map(fn($v) => (int) $v)->all();
            $dto->triggerWarnings = collect($helper->triggerWarnings)->filter(fn($tw) => in_array((int) $tw['id'], $twIds, true))->values()->all();
        }
        if ($fieldsToReturn->includeAuthors && $helper->profiles) {
            $dto->authors = collect($helper->profiles)->filter(fn($p) => in_array((int) $p->user_id, $story->authors->pluck('user_id')->map(fn($v) => (int) $v)->all(), true))->values()->all();
        }
        if ($fieldsToReturn->includeChapters) {
            $dto->chapters = $story->chapters->map(fn(Chapter $c) => StoryChapterDto::fromModel($c))->values()->all();
        }

        return $dto;
    }
}
