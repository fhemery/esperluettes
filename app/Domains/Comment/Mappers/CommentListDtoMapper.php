<?php

declare(strict_types=1);

namespace App\Domains\Comment\Mappers;

use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentUiConfigDto;
use App\Domains\Comment\Contracts\CommentDto;

class CommentListDtoMapper
{
    /**
     * @param CommentDto[] $items
     */
    public function make(string $entityType, int $entityId, int $page, int $perPage, int $total, array $items, CommentUiConfigDto $config): CommentListDto
    {
        return new CommentListDto(
            entityType: $entityType,
            entityId: $entityId,
            page: $page,
            perPage: $perPage,
            total: $total,
            items: $items,
            config: $config,
        );
    }
}
