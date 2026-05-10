<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Models;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('calendar_secret_gift_participants')]
#[Fillable(['activity_id', 'user_id', 'preferences'])]
class SecretGiftParticipant extends Model
{

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
