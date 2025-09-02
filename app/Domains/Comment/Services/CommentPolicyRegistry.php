<?php

namespace App\Domains\Comment\Services;

use App\Domains\Comment\Contracts\CommentPostingPolicy;
use App\Domains\Comment\Contracts\CommentToCreateDto;

class CommentPolicyRegistry
{
    /** @var array<string, CommentPostingPolicy> */
    private array $policies = [];

    public function register(string $entityType, CommentPostingPolicy $policy): void
    {
        $this->policies[$entityType] = $policy;
    }

    public function validateCreate(CommentToCreateDto $dto): void
    {
        $policy = $this->policies[$dto->entityType] ?? null;
        if ($policy) {
            $policy->validateCreate($dto);
        }
    }
}
