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
        private readonly AnnotationService $annotations,
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

        if ($this->isModerator($viewerId)) {
            return AnnotationListDto::ROLE_MODERATOR;
        }

        return null;
    }

    /**
     * Visible root-annotation count per root comment of one page: authors/co-authors and
     * moderators see every count, the commenter only their own, anyone else 0.
     * At most one policy call, one role lookup and one grouped COUNT, whatever the page size.
     *
     * @param Comment[] $rootComments
     * @return array<int,int> [commentId => count], one entry per given comment
     */
    public function visibleCounts(string $entityType, int $entityId, array $rootComments, int $viewerId): array
    {
        $ids = array_map(fn (Comment $c) => (int) $c->id, $rootComments);
        $counts = array_fill_keys($ids, 0);
        if ($viewerId <= 0 || $ids === []) {
            return $counts;
        }

        $seesAll = $this->policies->canMarkAsProcessed($entityType, $entityId, $viewerId)
            || $this->isModerator($viewerId);
        $visibleIds = $seesAll
            ? $ids
            : array_values(array_map(
                fn (Comment $c) => (int) $c->id,
                array_filter($rootComments, fn (Comment $c) => $c->author_id !== null && (int) $c->author_id === $viewerId),
            ));

        return array_replace($counts, $this->annotations->countRootsByComment($visibleIds));
    }

    private function isModerator(int $userId): bool
    {
        $roles = $this->authApi->getRolesByUserIds([$userId])[$userId] ?? [];
        $slugs = array_map(fn (RoleDto $role) => $role->slug, $roles);

        return array_intersect($slugs, self::MODERATOR_ROLES) !== [];
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
