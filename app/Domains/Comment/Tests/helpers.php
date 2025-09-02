<?php

/**
 * @param CommentPublicApi $api
 * @param int $authorId
 * @param string $entityType
 * @param int $entityId
 * @param string $body
 * @param int $parentCommentId
 * @return int the Id of the comment
 */
function createComment($api,string $entityType = 'default', int $entityId = 1,  string $body = 'Hello', int $parentCommentId = null): int
{

    return $api->create($entityType, $entityId, $body, $parentCommentId);
}

