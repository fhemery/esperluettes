<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Dto\RoleDto;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Comment\Public\Api\Contracts\AnnotationListDto;

/**
 * Who may see the annotations under a root comment, and with which actions.
 * Precedence: commenter, then author/co-author (per policy), then moderator.
 */
class AnnotationAccessService
{
    private const MODERATOR_ROLES = [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN];

    public function __construct(
        private readonly CommentPolicyRegistry $policies,
        private readonly AuthPublicApi $authApi,
    ) {}

    /**
     * @return string|null One of AnnotationListDto::ROLE_*, or null when the viewer may see nothing
     */
    public function resolveViewerRole(Comment $comment, int $viewerId): ?string
    {
        if ($viewerId <= 0) {
            return null;
        }

        if ($comment->author_id !== null && (int) $comment->author_id === $viewerId) {
            return AnnotationListDto::ROLE_COMMENTER;
        }

        if ($this->policies->canMarkAsProcessed((string) $comment->commentable_type, (int) $comment->commentable_id, $viewerId)) {
            return AnnotationListDto::ROLE_AUTHOR;
        }

        $roles = $this->authApi->getRolesByUserIds([$viewerId])[$viewerId] ?? [];
        $slugs = array_map(fn (RoleDto $role) => $role->slug, $roles);
        if (array_intersect($slugs, self::MODERATOR_ROLES) !== []) {
            return AnnotationListDto::ROLE_MODERATOR;
        }

        return null;
    }

    /**
     * The commenter sees only their own annotations; authors and moderators see all.
     */
    public function restrictToAuthorId(string $viewerRole, int $viewerId): ?int
    {
        return $viewerRole === AnnotationListDto::ROLE_COMMENTER ? $viewerId : null;
    }

    public function seesProcessedFlag(string $viewerRole): bool
    {
        return $viewerRole !== AnnotationListDto::ROLE_COMMENTER;
    }

    public function canMarkAsProcessed(string $viewerRole): bool
    {
        return $viewerRole === AnnotationListDto::ROLE_AUTHOR;
    }

    public function canDelete(string $viewerRole): bool
    {
        return $viewerRole === AnnotationListDto::ROLE_MODERATOR;
    }
}
