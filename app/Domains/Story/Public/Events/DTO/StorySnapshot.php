<?php

namespace App\Domains\Story\Public\Events\DTO;

use App\Domains\Shared\Support\WordCounter;
use App\Domains\Story\Private\Models\Story;

final class StorySnapshot
{
    public function __construct(
        public readonly int $storyId,
        public readonly int $createdByUserId,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $visibility,
        public readonly int $summaryWordCount,
        public readonly int $summaryCharCount,
        public readonly int $typeId,
        public readonly int $audienceId,
        public readonly int $copyrightId,
        public readonly ?int $statusId,
        public readonly ?int $feedbackId,
        /** @var int[] */ public readonly array $genreIds,
        /** @var int[] */ public readonly array $triggerWarningIds,
        public readonly ?bool $isComplete = null,
        public readonly ?bool $isExcludedFromEvents = null,
    ) {}

    public static function fromModel(Story $story, int $createdByUserId): self
    {
        $summaryWordCount = WordCounter::count($story->description);
        $summaryCharCount = mb_strlen(strip_tags($story->description));

        // Pluck referential pivot IDs; use known pivot column names
        $genreIds = $story->genres()->pluck('story_ref_genre_id')->map('intval')->all();
        $triggerWarningIds = $story->triggerWarnings()->pluck('story_ref_trigger_warning_id')->map('intval')->all();

        return new self(
            storyId: (int) $story->id,
            createdByUserId: $createdByUserId,
            title: (string) $story->title,
            slug: (string) $story->slug,
            visibility: (string) $story->visibility,
            summaryWordCount: (int) $summaryWordCount,
            summaryCharCount: (int) $summaryCharCount,
            typeId: (int) $story->story_ref_type_id,
            audienceId: (int) $story->story_ref_audience_id,
            copyrightId: (int) $story->story_ref_copyright_id,
            statusId: $story->story_ref_status_id ? (int) $story->story_ref_status_id : null,
            feedbackId: $story->story_ref_feedback_id ? (int) $story->story_ref_feedback_id : null,
            genreIds: $genreIds,
            triggerWarningIds: $triggerWarningIds,
            isComplete: isset($story->is_complete) ? (bool) $story->is_complete : null,
            isExcludedFromEvents: isset($story->is_excluded_from_events) ? (bool) $story->is_excluded_from_events : null,
        );
    }

    public function toPayload(): array
    {
        return [
            'storyId' => $this->storyId,
            'createdByUserId' => $this->createdByUserId,
            'title' => $this->title,
            'slug' => $this->slug,
            'visibility' => $this->visibility,
            'summaryWordCount' => $this->summaryWordCount,
            'summaryCharCount' => $this->summaryCharCount,
            'typeId' => $this->typeId,
            'audienceId' => $this->audienceId,
            'copyrightId' => $this->copyrightId,
            'statusId' => $this->statusId,
            'feedbackId' => $this->feedbackId,
            'genreIds' => array_values($this->genreIds),
            'triggerWarningIds' => array_values($this->triggerWarningIds),
            'isComplete' => $this->isComplete,
            'isExcludedFromEvents' => $this->isExcludedFromEvents,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            storyId: (int) ($payload['storyId'] ?? 0),
            createdByUserId: (int) ($payload['createdByUserId'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
            slug: (string) ($payload['slug'] ?? ''),
            visibility: (string) ($payload['visibility'] ?? ''),
            summaryWordCount: (int) ($payload['summaryWordCount'] ?? 0),
            summaryCharCount: (int) ($payload['summaryCharCount'] ?? 0),
            typeId: (int) ($payload['typeId'] ?? 0),
            audienceId: (int) ($payload['audienceId'] ?? 0),
            copyrightId: (int) ($payload['copyrightId'] ?? 0),
            statusId: isset($payload['statusId']) ? (int) $payload['statusId'] : null,
            feedbackId: isset($payload['feedbackId']) ? (int) $payload['feedbackId'] : null,
            genreIds: array_map('intval', (array) ($payload['genreIds'] ?? [])),
            triggerWarningIds: array_map('intval', (array) ($payload['triggerWarningIds'] ?? [])),
            isComplete: array_key_exists('isComplete', $payload) ? (bool) $payload['isComplete'] : null,
            isExcludedFromEvents: array_key_exists('isExcludedFromEvents', $payload) ? (bool) $payload['isExcludedFromEvents'] : null,
        );
    }
}
