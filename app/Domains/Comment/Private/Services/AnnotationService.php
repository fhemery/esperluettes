<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Services;

use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Private\Support\CommentBodySanitizer;
use App\Domains\Comment\Public\Api\Contracts\AnnotationToCreateDto;
use Illuminate\Database\Eloquent\Collection;

class AnnotationService
{
    public function __construct(
        private readonly CommentBodySanitizer $sanitizer,
    ) {}

    /**
     * Store root annotations under a freshly created root comment.
     * Callers validate and run this inside the comment's transaction.
     *
     * @param AnnotationToCreateDto[] $items
     */
    public function createForComment(int $commentId, int $authorId, array $items): void
    {
        foreach ($items as $item) {
            CommentAnnotation::query()->create([
                'comment_id' => $commentId,
                'parent_annotation_id' => null,
                'author_id' => $authorId,
                'body' => $this->sanitizer->sanitizeToHtml($item->body, CommentBodySanitizer::ANNOTATION),
                'highlighted_text' => $item->highlightedText,
                'prefix' => $item->prefix,
                'suffix' => $item->suffix,
            ]);
        }
    }

    /**
     * Live root annotations under a comment, oldest first, optionally limited to one author.
     *
     * @return Collection<int, CommentAnnotation>
     */
    public function getRootsForComment(int $commentId, ?int $authorId = null): Collection
    {
        return CommentAnnotation::query()
            ->roots()
            ->where('comment_id', $commentId)
            ->when($authorId !== null, fn ($q) => $q->where('author_id', $authorId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
