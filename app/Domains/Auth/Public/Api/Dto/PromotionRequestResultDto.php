<?php

namespace App\Domains\Auth\Public\Api\Dto;

/**
 * Result of a promotion request attempt.
 */
class PromotionRequestResultDto
{
    public const ERROR_ALREADY_PENDING = 'already_pending';
    public const ERROR_CRITERIA_NOT_MET = 'criteria_not_met';
    public const ERROR_ALREADY_CONFIRMED = 'already_confirmed';
    public const ERROR_USER_NOT_FOUND = 'user_not_found';

    public function __construct(
        public bool $success,
        public ?string $errorKey = null,
    ) {}

    public static function success(): self
    {
        return new self(success: true);
    }

    public static function error(string $errorKey): self
    {
        return new self(success: false, errorKey: $errorKey);
    }
}
