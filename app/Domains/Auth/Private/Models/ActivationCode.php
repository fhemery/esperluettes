<?php

namespace App\Domains\Auth\Private\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ActivationCode extends Model
{
    use HasFactory;

    protected $table = 'user_activation_codes';

    protected $fillable = [
        'code',
        'sponsor_user_id',
        'used_by_user_id',
        'comment',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'sponsor_user_id' => 'integer',
        'used_by_user_id' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the sponsor user who created/assigned this code
     */
    public function sponsorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    /**
     * Get the user who used this code
     */
    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    /**
     * Check if the activation code is valid for use
     */
    public function isValid(): bool
    {
        // Already used
        if ($this->used_at !== null) {
            return false;
        }

        // Expired
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the activation code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the activation code has been used
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Mark the code as used by a specific user
     */
    public function markAsUsed(User $user): void
    {
        $this->update([
            'used_by_user_id' => $user->id,
            'used_at' => now(),
        ]);
    }

    /**
     * Get the status of the activation code
     */
    public function getStatusAttribute(): string
    {
        if ($this->isUsed()) {
            return 'used';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'active';
    }
}
