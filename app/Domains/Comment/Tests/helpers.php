<?php

use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Comment\PublicApi\CommentPublicApi;

/**
 * @param CommentPublicApi $api
 * @param int $authorId
 * @param string $entityType
 * @param int $entityId
 * @param string $body
 * @param int $parentCommentId
 * @return int the Id of the comment
 */
function createComment($api,string $entityType = 'default', int $entityId = 1,  string $body = 'Hello', ?int $parentCommentId = null): int
{
    return $api->create(new CommentToCreateDto($entityType, $entityId, $body, $parentCommentId));
}

/**
 * @param CommentPublicApi $api
 * @param int $number
 * @param string $entityType
 * @param int $entityId
 * @param string $body
 * @param int $parentCommentId
 * @return int[] the Ids of the created comments
 */
function createSeveralComments($number,string $entityType = 'default', int $entityId = 1,  string $body = 'Hello', ?int $parentCommentId = null): array {
    $api = app(CommentPublicApi::class);
    $commentIds = [];
    for ($i = 0; $i < $number; $i++) {
        $commentIds[] = createComment($api, $entityType, $entityId, $body . ' ' . $i, $parentCommentId);
    }
    return $commentIds;
}

function getComment($api, int $commentId): CommentDto
{
    return $api->getComment($commentId);
}

function listComments($api, string $entityType = 'default', int $entityId = 1): CommentListDto
{
    return $api->getFor($entityType, $entityId, 1, 5);
}
