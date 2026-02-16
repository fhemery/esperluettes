<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Public\Api\StoryMapperHelper;

class StoryDto
{
    /**
     * @var array<int,array> $genres
     * @var array<int,array> $triggerWarnings
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
        public ?array $collaborators = null,
        public ?array $chapters = null,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $lastChapterPublishedAt = null,
        public ?int $publishedChaptersCount = null,
        public ?int $wordCount = null,
        public ?bool $isComplete = null,
        public ?string $coverType = null,
        public ?string $coverUrl = null,
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
            isComplete: isset($story->is_complete) ? (bool) $story->is_complete : null,
            coverType: (string) ($story->cover_type ?? 'default'),
            coverUrl: $helper->coverService->getCoverUrl($story),
        );

        if ($fieldsToReturn->includeGenreIds) {
            $genreIds = $story->genres->map(fn($v) => (int) $v->story_ref_genre_id)->all();
            $dto->genres = collect($helper->genres)->filter(fn($g) => in_array((int) $g['id'], $genreIds, true))->values()->all();
        }
        if ($fieldsToReturn->includeTriggerWarningIds) {
            $twIds = $story->triggerWarnings->map(fn($v) => (int) $v->story_ref_trigger_warning_id)->all();
            $dto->triggerWarnings = collect($helper->triggerWarnings)->filter(fn($tw) => in_array((int) $tw['id'], $twIds, true))->values()->all();
        }
        if ($fieldsToReturn->includeAuthors && $helper->profiles) {
            $dto->authors = collect($helper->profiles)->filter(fn($p) => in_array((int) $p->user_id, $story->authors->pluck('user_id')->map(fn($v) => (int) $v)->all(), true))->values()->all();
        }
        if ($fieldsToReturn->includeCollaborators && $helper->profiles) {
            $dto->collaborators = collect($helper->profiles)->filter(fn($p) => in_array((int) $p->user_id, $story->collaborators->pluck('user_id')->map(fn($v) => (int) $v)->all(), true))->values()->all();
        }
        if ($fieldsToReturn->includeChapters) {
            $dto->chapters = $story->chapters->map(fn(Chapter $c) => StoryChapterDto::fromModel($c))->values()->all();
        }
        if ($fieldsToReturn->includePublishedChaptersCount) {
            $dto->publishedChaptersCount = $story->published_chapters_count;
        }
        if ($fieldsToReturn->includeWordCount) {
            $dto->wordCount = $story->published_words_total;
        }

        return $dto;
    }
}
