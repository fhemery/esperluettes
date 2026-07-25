<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Contracts\Dto;

/**
 * One image in a scope, identified by its storage path, with a display URL.
 */
final class MediaPathDto
{
    public function __construct(
        public readonly string $path,
        public readonly string $url,
    ) {}

    /** @return array{path:string,url:string} */
    public function toArray(): array
    {
        return ['path' => $this->path, 'url' => $this->url];
    }
}
