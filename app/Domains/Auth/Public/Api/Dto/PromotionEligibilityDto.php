<?php

namespace App\Domains\Auth\Public\Api\Dto;

/**
 * Represents the eligibility status for a user to request promotion.
 */
class PromotionEligibilityDto
{
    public function __construct(
        /** Whether user meets all criteria and can request promotion */
        public bool $eligible,
        /** Whether user already has a pending request */
        public bool $hasPendingRequest,
        /** Number of days required to be registered (float for sub-day precision) */
        public float $daysRequired,
        /** Number of days elapsed since registration or last rejection (float for sub-day precision) */
        public float $daysElapsed,
        /** Number of comments required */
        public int $commentsRequired,
        /** Number of comments the user has posted (passed by caller) */
        public int $commentsPosted,
        /** Reason for last rejection, if any */
        public ?string $lastRejectionReason = null,
        /** Date of last rejection, if any */
        public ?\DateTime $lastRejectionDate = null,
    ) {}

    public function meetsTimeRequirement(): bool
    {
        return $this->daysElapsed >= $this->daysRequired;
    }

    public function meetsCommentRequirement(): bool
    {
        return $this->commentsPosted >= $this->commentsRequired;
    }
}
