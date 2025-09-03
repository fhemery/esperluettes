<?php

namespace App\Domains\Comment\PublicApi;

use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Comment\Services\CommentService;
use App\Domains\Comment\Services\CommentPolicyRegistry;
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

        // Map roots with their children
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
                $childrenDtos[] = new CommentDto(
                    id: (int) $child->getKey(),
                    body: (string) $child->body,
                    authorId: $cAuthorId,
                    authorProfile: $cProfile,
                    createdAt: $child->created_at?->toISOString() ?? '',
                    updatedAt: $child->updated_at?->toISOString(),
                );
            }

            $items[] = new CommentDto(
                id: (int) $model->getKey(),
                body: (string) $model->body,
                authorId: $authorId,
                authorProfile: $profile,
                createdAt: $model->created_at?->toISOString() ?? '',
                updatedAt: $model->updated_at?->toISOString(),
                children: $childrenDtos,
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
    public function create(CommentToCreateDto $comment): int
    {
        $this->checkAccess();

        $user = Auth::user();
        // Apply domain-specific posting policies if any
        $this->policies->validateCreate($comment);
        // Validate parent belongs to the same target if provided
        if ($comment->parentCommentId !== null) {
            $parent = $this->service->getComment($comment->parentCommentId);
            if (
                (string) $parent->commentable_type !== (string) $comment->entityType
                || (int) $parent->commentable_id !== (int) $comment->entityId
            ) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Parent comment target mismatch');
            }
        }
        return $this->service->postComment($comment->entityType, $comment->entityId, $user->id, $comment->body, $comment->parentCommentId)->id;
    }

    public function getComment(int $commentId): CommentDto
    {
        $this->checkAccess();
        $comment = $this->service->getComment($commentId);
        return CommentDto::fromModel($comment, $this->profiles->getPublicProfile($comment->author_id));
    }
}

