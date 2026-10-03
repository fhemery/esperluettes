<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api;

use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Private\Services\AnnotationAccessService;
use App\Domains\Comment\Private\Services\AnnotationService;
use App\Domains\Comment\Private\Services\CommentService;
use App\Domains\Comment\Public\Api\Contracts\AnnotationDto;
use App\Domains\Comment\Public\Api\Contracts\AnnotationListDto;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AnnotationPublicApi
{
    public function __construct(
        private readonly CommentService $comments,
        private readonly AnnotationService $annotations,
        private readonly AnnotationAccessService $access,
        private readonly ProfilePublicApi $profiles,
    ) {}

    /**
     * The commenter's root annotations under one root comment, filtered to what the viewer may see.
     *
     * @throws ModelNotFoundException when the comment is unknown or trashed
     * @throws AuthorizationException when the viewer may see none of them
     */
    public function getForComment(int $commentId, int $viewerId): AnnotationListDto
    {
        $comment = $this->comments->getComment($commentId);

        $role = $this->access->resolveViewerRole($comment, $viewerId);
        if ($role === null) {
            throw new AuthorizationException();
        }

        $models = $this->annotations->getRootsForComment($commentId, $this->access->restrictToAuthorId($role, $viewerId));

        $authorIds = $models->pluck('author_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $profiles = $authorIds === [] ? [] : $this->profiles->getPublicProfiles($authorIds);

        $seesProcessed = $this->access->seesProcessedFlag($role);
        $canMark = $this->access->canMarkAsProcessed($role);
        $canDelete = $this->access->canDelete($role);

        $items = $models->map(fn (CommentAnnotation $a) => new AnnotationDto(
            id: (int) $a->id,
            commentId: (int) $a->comment_id,
            parentAnnotationId: $a->parent_annotation_id,
            authorId: $a->author_id,
            authorProfile: $profiles[(int) $a->author_id] ?? new ProfileDto(
                user_id: (int) $a->author_id,
                display_name: '',
                slug: '',
                avatar_url: '',
            ),
            body: (string) $a->body,
            highlightedText: (string) $a->highlighted_text,
            prefix: $a->prefix,
            suffix: $a->suffix,
            isProcessed: $seesProcessed ? (bool) $a->is_processed : null,
            createdAt: $a->created_at?->toISOString() ?? '',
            replies: [],
            canMarkAsProcessed: $canMark,
            canDelete: $canDelete,
        ))->all();

        return new AnnotationListDto(
            commentId: (int) $comment->id,
            viewerRole: $role,
            items: $items,
        );
    }
}
