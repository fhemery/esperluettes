<?php

use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Comment\PublicApi\CommentPublicApi;

/**
 * @param int $authorId
 * @param string $entityType
 * @param int $entityId
 * @param string $body
 * @param int $parentCommentId
 * @return int the Id of the comment
 */
function createComment(string $entityType = 'default', int $entityId = 1,  string $body = 'Hello', ?int $parentCommentId = null): int
{
    /** @var CommentPublicApi $api */
    $api = app(CommentPublicApi::class);
    return $api->create(new CommentToCreateDto($entityType, $entityId, $body, $parentCommentId));
}

/**
 * @param int $number
 * @param string $entityType
 * @param int $entityId
 * @param string $body
 * @param int $parentCommentId
 * @return int[] the Ids of the created comments
 */
function createSeveralComments($number,string $entityType = 'default', int $entityId = 1,  string $body = 'Hello', ?int $parentCommentId = null): array {
    $commentIds = [];
    for ($i = 0; $i < $number; $i++) {
        $commentIds[] = createComment($entityType, $entityId, $body . ' ' . $i, $parentCommentId);
    }
    return $commentIds;
}

function getComment(int $commentId): CommentDto
{
    /** @var CommentPublicApi $api */
    $api = app(CommentPublicApi::class);
    return $api->getComment($commentId);
}

function listComments(string $entityType = 'default', int $entityId = 1, int $page = 1, int $perPage = 5): CommentListDto
{
    /** @var CommentPublicApi $api */
    $api = app(CommentPublicApi::class);
    return $api->getFor($entityType, $entityId, $page, $perPage);
}
