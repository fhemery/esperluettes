<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Models;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretGiftParticipant extends Model
{
    protected $table = 'calendar_secret_gift_participants';

    protected $fillable = [
        'activity_id',
        'user_id',
        'preferences',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
