<?php

namespace App\Domains\Auth\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRequest extends Model
{
    protected $table = 'user_promotion_request';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'status',
        'comment_count',
        'requested_at',
        'decided_at',
        'decided_by',
        'rejection_reason',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'comment_count' => 'integer',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
        'decided_by' => 'integer',
    ];

    /**
     * Get the user who made this promotion request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin/moderator who decided on this request.
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function accept(int $decidedBy): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'decided_at' => now(),
            'decided_by' => $decidedBy,
        ]);
    }

    public function reject(int $decidedBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'decided_at' => now(),
            'decided_by' => $decidedBy,
            'rejection_reason' => $reason,
        ]);
    }
}
