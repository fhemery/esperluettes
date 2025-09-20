<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    protected $table = 'story_reading_progress';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'story_id',
        'chapter_id',
        'read_at',
    ];

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
