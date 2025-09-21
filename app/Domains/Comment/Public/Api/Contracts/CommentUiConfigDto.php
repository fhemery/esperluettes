<?php

namespace App\Domains\Comment\Public\Api\Contracts;

class CommentUiConfigDto
{
    public function __construct(
        public readonly ?int $minRootCommentLength = null,
        public readonly ?int $maxRootCommentLength = null,
        public readonly bool $canCreateRoot = true,
        public readonly ?int $minReplyCommentLength = null,
        public readonly ?int $maxReplyCommentLength = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'min_root_comment_length' => $this->minRootCommentLength,
            'max_root_comment_length' => $this->maxRootCommentLength,
            'can_create_root' => $this->canCreateRoot,
            'min_reply_comment_length' => $this->minReplyCommentLength,
            'max_reply_comment_length' => $this->maxReplyCommentLength,
        ];
    }
}
