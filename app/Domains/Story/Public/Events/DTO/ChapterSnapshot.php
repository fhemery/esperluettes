<?php

namespace App\Domains\Story\Public\Events\DTO;

use App\Domains\Shared\Support\WordCounter;
use App\Domains\Story\Private\Models\Chapter;

final class ChapterSnapshot
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly int $sortOrder,
        public readonly string $status,
        public readonly int $wordCount,
        public readonly int $charCount,
    ) {}

    public static function fromModel(Chapter $chapter): self
    {
        $wordCount = WordCounter::count($chapter->content);
        $charCount = mb_strlen(strip_tags($chapter->content));

        return new self(
            id: (int) $chapter->id,
            title: (string) $chapter->title,
            slug: (string) $chapter->slug,
            sortOrder: (int) $chapter->sort_order,
            status: (string) $chapter->status,
            wordCount: (int) $wordCount,
            charCount: (int) $charCount,
        );
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sortOrder' => $this->sortOrder,
            'status' => $this->status,
            'wordCount' => $this->wordCount,
            'charCount' => $this->charCount,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            id: (int) ($payload['id'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
            slug: (string) ($payload['slug'] ?? ''),
            sortOrder: (int) ($payload['sortOrder'] ?? 0),
            status: (string) ($payload['status'] ?? ''),
            wordCount: (int) ($payload['wordCount'] ?? 0),
            charCount: (int) ($payload['charCount'] ?? 0),
        );
    }
}
