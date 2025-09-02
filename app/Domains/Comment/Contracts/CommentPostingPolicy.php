<?php

namespace App\Domains\Comment\Contracts;

interface CommentPostingPolicy
{
    /**
     * Validate whether the given user can create a comment, based on the provided DTO.
     * Should throw an exception (e.g., UnauthorizedException or ValidationException) if not allowed.
     */
    public function validateCreate(CommentToCreateDto $dto): void;
}
