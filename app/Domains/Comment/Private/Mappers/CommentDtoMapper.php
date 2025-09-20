<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Mappers;

use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Shared\Dto\ProfileDto;

class CommentDtoMapper
{
    /**
     * Map root comments with their children to DTOs and compute permission flags.
     *
     * @param Comment[] $models Roots with eager-loaded children
     * @param array<int, ProfileDto|null> $profiles Map of userId => ProfileDto|null
     * @return CommentDto[]
     */
    public function mapRootWithChildren(array $models, array $profiles, CommentPolicyRegistry $policies, string $entityType, int $userId): array
    {
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
                $childDto = CommentDto::fromModel($child, $cProfile);
                $childDto->canReply = $policies->canReply($entityType, $childDto, $userId);
                $childDto->canEditOwn = $policies->canEditOwn($entityType, $childDto, $userId);
                $childrenDtos[] = $childDto;
            }

            $rootDto = CommentDto::fromModel($model, $profile, $childrenDtos);
            $rootDto->canReply = $policies->canReply($entityType, $rootDto, $userId);
            $rootDto->canEditOwn = $policies->canEditOwn($entityType, $rootDto, $userId);
            $items[] = $rootDto;
        }
        return $items;
    }
}
