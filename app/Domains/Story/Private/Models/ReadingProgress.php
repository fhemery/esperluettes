<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('story_reading_progress', timestamps: false)]
#[Fillable(['user_id', 'story_id', 'chapter_id', 'read_at'])]
class ReadingProgress extends Model
{

    protected $casts = [
        'user_id' => 'integer',
        'story_id' => 'integer',
        'chapter_id' => 'integer',
        'read_at' => 'datetime',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
