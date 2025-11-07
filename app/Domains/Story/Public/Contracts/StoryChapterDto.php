<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Private\Models\Chapter;

class StoryChapterDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $authorNote = null,
        public int $wordCount = 0,
        public int $sortOrder = 0,
        public string $status = 'not_published',
        public ?\DateTimeInterface $firstPublishedAt = null,
        public int $readsLoggedCount = 0,
        public int $characterCount = 0,
        public ?bool $isRead = null,
    ) {
    }

    public static function fromModel(Chapter $c): self
    {
        return new self(
            id: (int) $c->id,
            title: (string) $c->title,
            slug: (string) $c->slug,
            authorNote: $c->author_note,
            wordCount: (int) ($c->word_count ?? 0),
            sortOrder: (int) ($c->sort_order ?? 0),
            status: (string) ($c->status ?? 'not_published'),
            firstPublishedAt: $c->first_published_at,
            readsLoggedCount: (int) ($c->reads_logged_count ?? 0),
            characterCount: (int) ($c->character_count ?? 0),
            isRead: $c->getIsRead(),
        );
    }
}
