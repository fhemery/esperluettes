<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Models;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('calendar_secret_gift_settings')]
#[Fillable(['activity_id', 'registration_ends_at'])]
class SecretGiftSettings extends Model
{
    protected $casts = ['registration_ends_at' => 'datetime'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
