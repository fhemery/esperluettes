<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api\Contracts;

/**
 * A commenter's pending changes under their own root comment, applied atomically.
 */
class AnnotationChangeSetDto
{
    /**
     * @param AnnotationToCreateDto[] $adds Each carrying its clientKey
     * @param array<int,string> $edits annotation id => new body
     * @param int[] $deletes annotation ids
     */
    public function __construct(
        public readonly array $adds = [],
        public readonly array $edits = [],
        public readonly array $deletes = [],
    ) {}
}
