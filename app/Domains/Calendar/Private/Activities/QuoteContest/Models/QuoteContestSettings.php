<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Models;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('calendar_quote_contest_settings')]
#[Fillable([
    'activity_id',
    'submissions_end_at',
    'votes_start_at',
    'notified_submissions_open_at',
    'notified_submissions_closing_at',
    'notified_votes_open_at',
    'notified_votes_closing_at',
])]
class QuoteContestSettings extends Model
{
    protected $casts = [
        'submissions_end_at' => 'datetime',
        'votes_start_at' => 'datetime',
        'notified_submissions_open_at' => 'datetime',
        'notified_submissions_closing_at' => 'datetime',
        'notified_votes_open_at' => 'datetime',
        'notified_votes_closing_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
