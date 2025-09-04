<?php

namespace App\Domains\Comment\PublicApi;

use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentUiConfigDto;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Comment\Services\CommentService;
use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Auth\PublicApi\AuthPublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class CommentPublicApi
{
    public function __construct(
        private CommentService $service,
        private ProfilePublicApi $profiles,
        private AuthPublicApi $authApi,
        private CommentPolicyRegistry $policies,
    ) {}

    public function checkAccess()
    {
        if (!$this->authApi->hasAnyRole([Roles::USER, Roles::USER_CONFIRMED])) {
            throw new UnauthorizedException('Unauthorized');
        }
    }

    public function getFor(string $entityType, int $entityId, int $page = 1, int $perPage = 20): CommentListDto
    {
        $this->checkAccess();
        $userId = (int) (Auth::id() ?? 0);

        // Lazy mode: page <= 0 → return config and total only, no items
        if ($page <= 0) {
            $total = $this->service->countFor($entityType, $entityId);
            return new CommentListDto(
                entityType: $entityType,
                entityId: (string) $entityId,
                page: 0,
                perPage: $perPage,
                total: $total,
                items: [],
                config: new CommentUiConfigDto(
                    minRootCommentLength: $this->policies->getRootCommentMinLength($entityType),
                    maxRootCommentLength: $this->policies->getRootCommentMaxLength($entityType),
                    canCreateRoot: $this->policies->canCreateRoot($entityType, (int) $entityId, $userId),
                    minReplyCommentLength: $this->policies->getReplyCommentMinLength($entityType),
                    maxReplyCommentLength: $this->policies->getReplyCommentMaxLength($entityType),
                ),
            );
        }

        $paginator = $this->service->getFor($entityType, $entityId, $page, $perPage, withChildren: true);

        $models = $paginator->items(); // roots only, children eager-loaded

        // Collect author ids from roots and their children
        $authorIds = [];
        foreach ($models as $model) {
            $authorIds[] = (int) $model->author_id;
            foreach ($model->children as $child) {
                $authorIds[] = (int) $child->author_id;
            }
        }
        $authorIds = array_values(array_unique($authorIds));
        $profiles = $this->profiles->getPublicProfiles($authorIds); // [userId => ProfileDto|null]

        // Map roots with their children and attach permissions
        $items = [];
        foreach ($models as $model) {
            $authorId = (int) $model->author_id;
            $profile = $profiles[$authorId] ?? new ProfileDto(
                user_id: $authorId,
                display_name: '',
                slug: '',
                avatar_url: '',
            );

            $childrenDtos = [];
            foreach ($model->children as $child) {
                $cAuthorId = (int) $child->author_id;
                $cProfile = $profiles[$cAuthorId] ?? new ProfileDto(
                    user_id: $cAuthorId,
                    display_name: '',
                    slug: '',
                    avatar_url: '',
                );
                // Provisional DTO for policy checks
                $childDto = CommentDto::fromModel($child, $cProfile);
                $childDto->canReply = $this->policies->canReply($entityType, $childDto, $userId);
                $childDto->canEditOwn = $this->policies->canEditOwn($entityType, $childDto, $userId);
                $childrenDtos[] = $childDto;
            }

            // Provisional root DTO for policy checks
            $rootCommentDto = CommentDto::fromModel($model, $profile, $childrenDtos);
            $rootCommentDto->canReply = $this->policies->canReply($entityType, $rootCommentDto, $userId);
            $rootCommentDto->canEditOwn = $this->policies->canEditOwn($entityType, $rootCommentDto, $userId);
            $items[] = $rootCommentDto;
        }

        return new CommentListDto(
            entityType: $entityType,
            entityId: (string) $entityId,
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            items: $items,
            config: new CommentUiConfigDto(
                minRootCommentLength: $this->policies->getRootCommentMinLength($entityType),
                maxRootCommentLength: $this->policies->getRootCommentMaxLength($entityType),
                canCreateRoot: $this->policies->canCreateRoot($entityType, (int) $entityId, $userId),
                minReplyCommentLength: $this->policies->getReplyCommentMinLength($entityType),
                maxReplyCommentLength: $this->policies->getReplyCommentMaxLength($entityType),
            ),
        );
    }

    /**
     * Create a root comment for a given target. Policies/validation deferred to service in future.
     * 
     * @return int The id of the created comment
     */
    public function create(CommentToCreateDto $comment): int
    {
        $this->checkAccess();

        $user = Auth::user();
        // Clean and compute plain length once
        $clean = Purifier::clean($comment->body, 'strict');
        $plain = is_string($clean) ? trim(strip_tags($clean)) : '';
        $len = mb_strlen($plain);

        if ($comment->parentCommentId === null) {
            // Root comment path
            $allowed = $this->policies->canCreateRoot($comment->entityType, (int) $comment->entityId, (int) $user->id);
            if (!$allowed) {
                throw ValidationException::withMessages(['body' => ['Comment not allowed']]);
            }
            $min = $this->policies->getRootCommentMinLength($comment->entityType);
            if ($min !== null && $len < $min) {
                throw ValidationException::withMessages(['body' => ['Comment too short']]);
            }
            $max = $this->policies->getRootCommentMaxLength($comment->entityType);
            if ($max !== null && $len > $max) {
                throw ValidationException::withMessages(['body' => ['Comment too long']]);
            }
        } else {
            // Reply path: validate parent first
            $parent = $this->service->getComment($comment->parentCommentId);
            if (
                (string) $parent->commentable_type !== (string) $comment->entityType
                || (int) $parent->commentable_id !== (int) $comment->entityId
            ) {
                throw ValidationException::withMessages(['parent_comment_id' => ['Parent comment target mismatch']]);
            }
            // Enforce that parent comment is a root comment (no parent)
            if ($parent->parent_comment_id !== null) {
                throw ValidationException::withMessages(['parent_comment_id' => ['Parent comment must be a root comment']]);
            }
            // Apply reply-specific length limits
            $min = $this->policies->getReplyCommentMinLength($comment->entityType);
            if ($min !== null && $len < $min) {
                throw ValidationException::withMessages(['body' => ['Comment too short']]);
            }
            $max = $this->policies->getReplyCommentMaxLength($comment->entityType);
            if ($max !== null && $len > $max) {
                throw ValidationException::withMessages(['body' => ['Comment too long']]);
            }
        }

        // Apply domain-specific posting policies if any
        $this->policies->validateCreate($comment);

        return $this->service->postComment($comment->entityType, $comment->entityId, $user->id, $comment->body, $comment->parentCommentId)->id;
    }

    public function getComment(int $commentId): CommentDto
    {
        $this->checkAccess();
        $comment = $this->service->getComment($commentId);
        return CommentDto::fromModel($comment, $this->profiles->getPublicProfile($comment->author_id));
    }

    /**
     * Public API to check if the current or specified user has already posted a root comment
     * on a given entity. Prefer using the explicit $userId for policies.
     */
    public function userHasRoot(string $entityType, int $entityId, int $userId): bool
    {
        // No access gate here: this is intended for internal policy checks where $userId is provided
        return $this->service->userHasRoot($entityType, $entityId, $userId);
    }
}

