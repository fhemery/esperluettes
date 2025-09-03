<?php

namespace App\Domains\Comment\Contracts;

class CommentUiConfigDto
{
    public function __construct(
        public readonly ?int $minBodyLength,
        public readonly ?int $maxBodyLength,
        public readonly bool $canCreateRoot,
    ) {
    }

    public function toArray(): array
    {
        return [
            'min_body_length' => $this->minBodyLength,
            'max_body_length' => $this->maxBodyLength,
            'can_create_root' => $this->canCreateRoot,
        ];
    }
}
