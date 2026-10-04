<?php

namespace App\Domains\Comment\Public\Api\Contracts;

class AnnotationToCreateDto
{
    /**
     * @param string|null $clientKey The client's key for this pending add (saveChanges only);
     *                               errors on the item are reported under `adds.<clientKey>`.
     */
    public function __construct(
        public readonly string $body,
        public readonly string $highlightedText,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly ?string $clientKey = null,
    ) {}
}
