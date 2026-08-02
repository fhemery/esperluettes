<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('calendar_quote_contest_entries')]
#[Fillable([
    'activity_id',
    'category_id',
    'user_id',
    'quote_id',
    'story_id',
    'highlighted_text',
    'story_title',
    'story_slug',
    'chapter_id',
    'chapter_title',
    'chapter_slug',
    'author_user_ids',
    'withdrawn_at',
])]
class QuoteContestEntry extends Model
{
    protected $casts = [
        'author_user_ids' => 'array',
        'withdrawn_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuoteContestCategory::class, 'category_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuoteContestVote::class, 'entry_id');
    }
}
