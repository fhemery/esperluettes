<?php

namespace App\Domains\Comment\Public\Api\Contracts;

class AnnotationToCreateDto
{
    public function __construct(
        public readonly string $body,
        public readonly string $highlightedText,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
    ) {}
}
