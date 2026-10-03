<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api;

use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Private\Services\AnnotationAccessService;
use App\Domains\Comment\Private\Services\AnnotationService;
use App\Domains\Comment\Private\Services\CommentService;
use App\Domains\Comment\Private\Support\AnnotationItemValidator;
use App\Domains\Comment\Public\Api\Contracts\AnnotationChangeSetDto;
use App\Domains\Comment\Public\Api\Contracts\AnnotationDto;
use App\Domains\Comment\Public\Api\Contracts\AnnotationListDto;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class AnnotationPublicApi
{
    public function __construct(
        private readonly CommentService $comments,
        private readonly AnnotationService $annotations,
        private readonly AnnotationAccessService $access,
        private readonly ProfilePublicApi $profiles,
        private readonly CommentPolicyRegistry $policies,
        private readonly AnnotationItemValidator $itemValidator,
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
        $replies = $this->access->filterActiveReplyWriters(
            $this->annotations->getRepliesForRoots($models->pluck('id')->map(fn ($id) => (int) $id)->all()),
        );
        $repliesByRoot = $replies->groupBy('parent_annotation_id');

        $authorIds = $models->concat($replies)->pluck('author_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $profiles = $authorIds === [] ? [] : $this->profiles->getPublicProfiles($authorIds);
        $profileOf = fn (CommentAnnotation $a) => $profiles[(int) $a->author_id] ?? new ProfileDto(
            user_id: (int) $a->author_id,
            display_name: '',
            slug: '',
            avatar_url: '',
        );

        $seesProcessed = $this->access->seesProcessedFlag($role);
        $canMark = $this->access->canMarkAsProcessed($role);
        $canDelete = $this->access->canDelete($role);
        $isCommenter = $role === AnnotationListDto::ROLE_COMMENTER;

        $items = $models->map(function (CommentAnnotation $a) use ($repliesByRoot, $profileOf, $seesProcessed, $canMark, $canDelete, $isCommenter, $role, $viewerId) {
            $rootReplies = $repliesByRoot->get($a->id, collect());

            return new AnnotationDto(
                id: (int) $a->id,
                commentId: (int) $a->comment_id,
                parentAnnotationId: $a->parent_annotation_id,
                authorId: $a->author_id,
                authorProfile: $profileOf($a),
                body: (string) $a->body,
                highlightedText: (string) $a->highlighted_text,
                prefix: $a->prefix,
                suffix: $a->suffix,
                isProcessed: $seesProcessed ? (bool) $a->is_processed : null,
                createdAt: $a->created_at?->toISOString() ?? '',
                replies: $rootReplies->map(fn (CommentAnnotation $r) => new AnnotationDto(
                    id: (int) $r->id,
                    commentId: (int) $r->comment_id,
                    parentAnnotationId: $r->parent_annotation_id,
                    authorId: $r->author_id,
                    authorProfile: $profileOf($r),
                    body: (string) $r->body,
                    highlightedText: '',
                    prefix: null,
                    suffix: null,
                    isProcessed: null,
                    createdAt: $r->created_at?->toISOString() ?? '',
                    replies: [],
                    canMarkAsProcessed: false,
                    canDelete: $canDelete || ($r->author_id !== null && (int) $r->author_id === $viewerId),
                    canEdit: false,
                    canReply: false,
                ))->values()->all(),
                canMarkAsProcessed: $canMark,
                canDelete: $canDelete,
                canEdit: $isCommenter && $a->author_id !== null && (int) $a->author_id === $viewerId,
                canReply: $this->access->canReply($role, $a, $rootReplies, $viewerId),
            );
        })->all();

        return new AnnotationListDto(
            commentId: (int) $comment->id,
            viewerRole: $role,
            items: $items,
        );
    }

    /**
     * Apply a commenter's pending changes under their own root comment, atomically.
     *
     * @return AnnotationListDto the viewer's list after the save
     * @throws ModelNotFoundException when the root comment is unknown or trashed
     * @throws AuthorizationException unless the viewer wrote this root comment and may annotate its entity
     * @throws ValidationException when any item is invalid or stale, keyed adds.<key> / edits.<id> / deletes.<id>
     */
    public function saveChanges(int $commentId, int $byUserId, AnnotationChangeSetDto $changes): AnnotationListDto
    {
        $comment = $this->comments->getComment($commentId);
        $type = (string) $comment->commentable_type;

        if (
            $comment->parent_comment_id !== null
            || (int) $comment->author_id !== $byUserId
            || !$this->policies->canAnnotate($type, (int) $comment->commentable_id, $byUserId)
        ) {
            throw new AuthorizationException();
        }

        $errors = [];
        $stale = [__('comment::annotations.errors.stale')];

        // A foreign, reply, deleted or other-comment id all read as stale: no 403, nothing to tell apart.
        $ownIds = $this->annotations->filterOwnRootIds(
            $commentId,
            $byUserId,
            array_merge(array_keys($changes->edits), $changes->deletes),
        );

        foreach ($changes->deletes as $id) {
            if (!in_array($id, $ownIds, true)) {
                $errors['deletes.' . $id] = $stale;
            }
        }

        foreach ($changes->edits as $id => $body) {
            if (!in_array($id, $ownIds, true) || in_array($id, $changes->deletes, true)) {
                $errors['edits.' . $id] = $stale;
                continue;
            }
            $error = $this->itemValidator->firstError($type, $body, null, null, null);
            if ($error !== null) {
                $errors['edits.' . $id] = [__($error)];
            }
        }

        foreach ($changes->adds as $add) {
            $error = $this->itemValidator->firstError($type, $add->body, $add->highlightedText, $add->prefix, $add->suffix);
            if ($error !== null) {
                $errors['adds.' . $add->clientKey] = [__($error)];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->annotations->applyChanges($commentId, $byUserId, $changes);

        return $this->getForComment($commentId, $byUserId);
    }

    /**
     * Author/co-author toggle of the "processed" flag on a root annotation.
     *
     * @throws ModelNotFoundException when the annotation or its comment is unknown or trashed
     * @throws AuthorizationException unless the user is an author of the commented entity
     * @throws ValidationException when the annotation is a reply
     */
    public function setProcessed(int $annotationId, int $byUserId, bool $value): void
    {
        $annotation = $this->annotations->getAnnotation($annotationId);
        $comment = $this->comments->getComment((int) $annotation->comment_id);

        $role = $this->access->resolveViewerRole($comment, $byUserId);
        if ($role === null || !$this->access->canMarkAsProcessed($role)) {
            throw new AuthorizationException();
        }

        if ($annotation->parent_annotation_id !== null) {
            throw ValidationException::withMessages([
                'annotation' => [__('comment::annotations.errors.reply_not_processable')],
            ]);
        }

        $this->annotations->setProcessed($annotation, $value);
    }

    /**
     * Soft-delete one annotation and its replies. Callers gate on the moderator roles.
     *
     * @throws ModelNotFoundException when the annotation is unknown or already deleted
     */
    public function moderatorDelete(int $annotationId, int $byUserId): void
    {
        $this->annotations->moderatorDelete($annotationId);
    }
}
