<?php

namespace App\Domains\Comment\Private\Support\Moderation;

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Private\Services\AnnotationAccessService;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Moderation\Public\Contracts\ReportGuardInterface;
use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;

class AnnotationSnapshotFormatter implements SnapshotFormatterInterface, ReportGuardInterface
{
    public function __construct(
        private readonly CommentPolicyRegistry $policyRegistry,
        private readonly AnnotationAccessService $access,
    ) {}

    public function canReport(int $entityId, int $reporterId): bool
    {
        return $this->access->canReportById($entityId, $reporterId);
    }

    public function capture(int $entityId): array
    {
        $annotation = CommentAnnotation::find($entityId);
        if (! $annotation) {
            return [];
        }

        $snapshot = [
            'highlighted_text' => (string) $annotation->highlighted_text,
            'body' => (string) $annotation->body,
        ];

        if ($annotation->parent_annotation_id !== null) {
            $parent = CommentAnnotation::withTrashed()->find($annotation->parent_annotation_id);
            $snapshot['parent'] = [
                'highlighted_text' => (string) ($parent?->highlighted_text ?? ''),
                'body' => (string) ($parent?->body ?? ''),
            ];
        }

        return $snapshot;
    }

    public function render(array $snapshot): string
    {
        $parent = $snapshot['parent'] ?? null;

        return view('comment::moderation.annotation-snapshot', [
            'highlightedText' => (string) ($snapshot['highlighted_text'] ?? ''),
            'body' => (string) ($snapshot['body'] ?? ''),
            'parent' => is_array($parent) ? [
                'highlighted_text' => (string) ($parent['highlighted_text'] ?? ''),
                'body' => (string) ($parent['body'] ?? ''),
            ] : null,
        ])->render();
    }

    public function getReportedUserId(int $entityId): int
    {
        $annotation = CommentAnnotation::find($entityId);

        return $annotation && $annotation->author_id !== null ? (int) $annotation->author_id : 0;
    }

    public function getContentUrl(int $entityId): string
    {
        $annotation = CommentAnnotation::find($entityId);
        $comment = $annotation ? Comment::withTrashed()->find($annotation->comment_id) : null;
        if (! $comment) {
            return '/';
        }

        return $this->policyRegistry->getUrl(
            (string) $comment->commentable_type,
            (int) $comment->commentable_id,
            (int) $comment->id,
        ) ?? '/';
    }
}
