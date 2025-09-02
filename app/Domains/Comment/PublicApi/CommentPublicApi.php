<?php

namespace App\Domains\Comment\PublicApi;

use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Services\CommentService;
use App\Domains\Shared\Contracts\ProfilePublicApi;

class CommentPublicApi
{
    public function __construct(
        private CommentService $service,
        private ProfilePublicApi $profiles,
    ) {}

    public function getFor(string $entityType, string $entityId, int $page = 1, int $perPage = 20): CommentListDto
    {
        $paginator = $this->service->getFor($entityType, $entityId, $page, $perPage);

        $models = $paginator->items();
        $authorIds = array_values(array_unique(array_map(fn($m) => (int) $m->author_id, $models)));
        $profiles = $this->profiles->getPublicProfiles($authorIds); // [userId => ProfileDto|null]

        $items = [];
        foreach ($models as $model) {
            $authorId = (int) $model->author_id;
            $profile = $profiles[$authorId] ?? new \App\Domains\Shared\Dto\ProfileDto(
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
            entityId: $entityId,
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            items: $items,
        );
    }
}
