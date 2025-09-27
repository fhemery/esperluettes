<?php
declare(strict_types=1);

namespace App\Domains\Comment\Public\Api;

use App\Domains\Comment\Public\Api\Contracts\CommentListDto;
use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use App\Domains\Comment\Public\Api\Contracts\CommentUiConfigDto;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Comment\Private\Mappers\CommentDtoMapper;
use App\Domains\Comment\Private\Mappers\CommentListDtoMapper;
use App\Domains\Comment\Private\Services\CommentService;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Comment\Private\Support\CommentBodySanitizer;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

class CommentPublicApi
{
    public function __construct(
        private CommentService $service,
        private ProfilePublicApi $profiles,
        private AuthPublicApi $authApi,
        private CommentPolicyRegistry $policies,
        private readonly CommentBodySanitizer $sanitizer,
        private readonly CommentDtoMapper $dtoMapper = new CommentDtoMapper(),
        private readonly CommentListDtoMapper $listDtoMapper = new CommentListDtoMapper(),
    ) {}

    public function checkAccess()
    {
        if (!$this->authApi->hasAnyRole([Roles::USER, Roles::USER_CONFIRMED])) {
            throw new UnauthorizedException('Unauthorized');
        }
    }

    

    /**
     * Bulk: for each target, determine if there exists at least one root comment without a reply
     * from any of the provided author ids.
     * @return array<int,bool> [entityId => hasUnreplied]
     */
    public function getHasUnrepliedRootsByAuthorsForTargets(string $entityType, array $entityIds, array $authorIds): array
    {
        return $this->service->hasUnrepliedRootsByAuthors($entityType, $entityIds, $authorIds);
    }

    /**
     * Bulk: get root comment counts for the given targets.
     * @return array<int,int> [entityId => count]
     */
    public function getNbRootCommentsFor(string $entityType, array $entityIds): array
    {
        return $this->service->countFor($entityType, $entityIds, true);
    }
    
    public function getNbRootComments(string $entityType, int $entityId, ?int $authorId =null): int
    {
        $counts = $this->service->countFor($entityType, [$entityId], true, $authorId);
        return $counts[$entityId] ?? 0;
    }

    public function getFor(string $entityType, int $entityId, int $page = 1, int $perPage = 20): CommentListDto
    {
        $this->checkAccess();
        $userId = (int) (Auth::id() ?? 0);

        // Lazy mode: page <= 0 → return config and total only, no items
        if ($page <= 0) {
            $total = $this->service->countFor($entityType, $entityId, true);
            return $this->listDtoMapper->make(
                entityType: $entityType,
                entityId: $entityId,
                page: 0,
                perPage: $perPage,
                total: $total,
                items: [],
                config: new CommentUiConfigDto(
                    minRootCommentLength: $this->policies->getRootCommentMinLength($entityType),
                    maxRootCommentLength: $this->policies->getRootCommentMaxLength($entityType),
                    canCreateRoot: $this->policies->canCreateRoot($entityType, $entityId, $userId),
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

        // Map roots with their children and attach permissions via mapper
        $items = $this->dtoMapper->mapRootWithChildren($models, $profiles, $this->policies, $entityType, $userId);

        return $this->listDtoMapper->make(
            entityType: $entityType,
            entityId: $entityId,
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            items: $items,
            config: new CommentUiConfigDto(
                minRootCommentLength: $this->policies->getRootCommentMinLength($entityType),
                maxRootCommentLength: $this->policies->getRootCommentMaxLength($entityType),
                canCreateRoot: $this->policies->canCreateRoot($entityType, $entityId, $userId),
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
        // Compute plain length once using sanitizer
        $len = $this->sanitizer->plainTextLength($comment->body);

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
        $userId = (int) (Auth::id() ?? 0);
        $profile = $this->profiles->getPublicProfile($comment->author_id);
        $dto = CommentDto::fromModel($comment, $profile);
        $dto->canReply = $this->policies->canReply($comment->commentable_type, $dto, $userId);
        $dto->canEditOwn = $this->policies->canEditOwn($comment->commentable_type, $dto, $userId);
        return $dto;
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

    /**
     * Edit a comment by id. Only the owner can edit. Policies decide if edit is allowed and constraints.
     */
    public function edit(int $commentId, string $newBody): CommentDto
    {
        $this->checkAccess();

        $user = Auth::user();
        $userId = (int) $user->id;
        $model = $this->service->getComment($commentId);

        // Ownership check
        if ($model->author_id !== $userId) {
            throw new UnauthorizedException('Cannot edit comment you do not own');
        }

        // Build provisional DTO for policy checks
        $profile = $this->profiles->getPublicProfile($model->author_id);
        $dto = CommentDto::fromModel($model, $profile);

        // High-level policy gate for edit
        if (!$this->policies->canEditOwn($model->commentable_type, $dto, $userId)) {
            throw ValidationException::withMessages(['body' => ['Edit not allowed']]);
        }

        // Enforce min/max policy by type (root vs reply) on plain text length of the sanitized body
        $len = $this->sanitizer->plainTextLength($newBody);
        $isRoot = $model->parent_comment_id === null;
        if ($isRoot) {
            $min = $this->policies->getRootCommentMinLength($model->commentable_type);
            if ($min !== null && $len < $min) {
                throw ValidationException::withMessages(['body' => ['Comment too short']]);
            }
            $max = $this->policies->getRootCommentMaxLength($model->commentable_type);
            if ($max !== null && $len > $max) {
                throw ValidationException::withMessages(['body' => ['Comment too long']]);
            }
        } else {
            $min = $this->policies->getReplyCommentMinLength($model->commentable_type);
            if ($min !== null && $len < $min) {
                throw ValidationException::withMessages(['body' => ['Comment too short']]);
            }
            $max = $this->policies->getReplyCommentMaxLength($model->commentable_type);
            if ($max !== null && $len > $max) {
                throw ValidationException::withMessages(['body' => ['Comment too long']]);
            }
        }

        // Domain-specific edit validation
        $this->policies->validateEdit($model->commentable_type, $dto, $userId, $newBody);

        // Perform update via service (sanitizes and persists)
        $updated = $this->service->updateComment($commentId, $newBody);

        // Return updated DTO with permissions flags
        $updatedDto = CommentDto::fromModel($updated, $profile);
        $updatedDto->canReply = $this->policies->canReply($updated->commentable_type, $updatedDto, $userId);
        $updatedDto->canEditOwn = $this->policies->canEditOwn($updated->commentable_type, $updatedDto, $userId);
        return $updatedDto;
    }
}

