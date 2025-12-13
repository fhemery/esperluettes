<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Models;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretGiftAssignment extends Model
{
    protected $table = 'calendar_secret_gift_assignments';

    protected $fillable = [
        'activity_id',
        'giver_user_id',
        'recipient_user_id',
        'gift_text',
        'gift_image_path',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function hasGift(): bool
    {
        return $this->gift_text !== null || $this->gift_image_path !== null;
    }
}
