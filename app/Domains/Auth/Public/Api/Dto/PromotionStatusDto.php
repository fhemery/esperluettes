<?php

namespace App\Domains\Auth\Public\Api\Dto;

/**
 * Current promotion status for dashboard display.
 */
class PromotionStatusDto
{
    public const STATUS_NONE = 'none';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        /** Current status: 'none', 'pending', or 'rejected' */
        public string $status,
        /** Reason for rejection, if status is 'rejected' */
        public ?string $rejectionReason = null,
        /** Date of rejection, if status is 'rejected' */
        public ?\DateTime $rejectionDate = null,
    ) {}

    public static function none(): self
    {
        return new self(status: self::STATUS_NONE);
    }

    public static function pending(): self
    {
        return new self(status: self::STATUS_PENDING);
    }

    public static function rejected(string $reason, \DateTime $date): self
    {
        return new self(
            status: self::STATUS_REJECTED,
            rejectionReason: $reason,
            rejectionDate: $date,
        );
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isNone(): bool
    {
        return $this->status === self::STATUS_NONE;
    }
}
