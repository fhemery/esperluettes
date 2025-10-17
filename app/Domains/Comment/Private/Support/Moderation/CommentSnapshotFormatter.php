<?php

namespace App\Domains\Comment\Private\Support\Moderation;

use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use App\Domains\Comment\Private\Models\Comment;

class CommentSnapshotFormatter implements SnapshotFormatterInterface
{
    public function capture(int $entityId): array
    {
        /** @var Comment|null $comment */
        $comment = Comment::find($entityId);
        if (! $comment) {
            return [];
        }

        return [
            'body' => $comment->body,
        ];
    }

    public function render(array $snapshot): string
    {
        return view('comment::moderation.comment-snapshot', [
            'body' => (string)($snapshot['body'] ?? ''),
        ])->render();
    }

    public function getReportedUserId(int $entityId): int
    {
        /** @var Comment|null $comment */
        $comment = Comment::find($entityId);
        return $comment && $comment->author_id !== null ? (int)$comment->author_id : 0;
    }

    public function getContentUrl(int $entityId): string
    {
        /** @var Comment|null $comment */
        $comment = Comment::find($entityId);
        if (! $comment) {
            return '/';
        }
        return route('comments.fragments', [
            'entity_type' => $comment->commentable_type,
            'entity_id' => $comment->commentable_id,
        ]);
    }
}
