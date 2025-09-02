<?php

namespace App\Domains\Comment\PublicApi;

use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Services\CommentService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Auth\PublicApi\AuthPublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;

class CommentPublicApi
{
    public function __construct(
        private CommentService $service,
        private ProfilePublicApi $profiles,
        private AuthPublicApi $authApi,
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
        $paginator = $this->service->getFor($entityType, $entityId, $page, $perPage);

        $models = $paginator->items();
        $authorIds = array_values(array_unique(array_map(fn($m) => (int) $m->author_id, $models)));
        $profiles = $this->profiles->getPublicProfiles($authorIds); // [userId => ProfileDto|null]

        $items = [];
        foreach ($models as $model) {
            $authorId = (int) $model->author_id;
            $profile = $profiles[$authorId] ?? new ProfileDto(
                user_id: $authorId,
                display_name: '',
                slug: '',
                avatar_url: '',
            );

            $items[] = new CommentDto(
                id: (int) $model->getKey(),
                body: (string) $model->body,
                authorId: $authorId,
                authorProfile: $profile,
                createdAt: $model->created_at?->toISOString() ?? '',
                updatedAt: $model->updated_at?->toISOString(),
            );
        }

        return new CommentListDto(
            entityType: $entityType,
            entityId: (string) $entityId,
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            items: $items,
        );
    }

    /**
     * Create a root comment for a given target. Policies/validation deferred to service in future.
     * 
     * @return int The id of the created comment
     */
    public function create(string $entityType, int $entityId, string $body, ?int $parentCommentId = null): int
    {
        $this->checkAccess();

        $user = Auth::user();
        return $this->service->postComment($entityType, $entityId, $user->id, $body, $parentCommentId)->id;
    }

    public function getComment(int $commentId): CommentDto
    {
        $this->checkAccess();
        $comment = $this->service->getComment($commentId);
        return CommentDto::fromModel($comment, $this->profiles->getPublicProfile($comment->author_id));
    }
}

