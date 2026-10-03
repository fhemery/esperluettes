<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Services;

use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Private\Support\CommentBodySanitizer;
use App\Domains\Comment\Public\Api\Contracts\AnnotationToCreateDto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when unknown or soft-deleted
     */
    public function getAnnotation(int $annotationId): CommentAnnotation
    {
        return CommentAnnotation::query()->findOrFail($annotationId);
    }

    public function setProcessed(CommentAnnotation $annotation, bool $value): void
    {
        $annotation->update([
            'is_processed' => $value,
            'processed_at' => $value ? now() : null,
        ]);
    }

    /**
     * Soft-delete one annotation and its replies.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when unknown or already deleted
     */
    public function moderatorDelete(int $annotationId): void
    {
        $annotation = $this->getAnnotation($annotationId);

        DB::transaction(function () use ($annotation) {
            CommentAnnotation::query()
                ->where('parent_annotation_id', $annotation->id)
                ->delete();
            $annotation->delete();
        });
    }

    /**
     * Live root annotations per comment, in one grouped query. Comments without any are absent.
     *
     * @param int[] $commentIds
     * @return array<int,int> [commentId => count]
     */
    public function countRootsByComment(array $commentIds): array
    {
        if ($commentIds === []) {
            return [];
        }

        return CommentAnnotation::query()
            ->roots()
            ->whereIn('comment_id', $commentIds)
            ->groupBy('comment_id')
            ->selectRaw('comment_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'comment_id')
            ->map(fn ($count) => (int) $count)
            ->all();
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
