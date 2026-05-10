<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('calendar_jardino_story_snapshots')]
#[Fillable(['goal_id', 'story_id', 'story_title', 'initial_word_count', 'current_word_count', 'biggest_word_count', 'selected_at', 'deselected_at'])]
class JardinoStorySnapshot extends Model
{

    protected $casts = [
        'selected_at' => 'datetime',
        'deselected_at' => 'datetime',
        'initial_word_count' => 'integer',
        'current_word_count' => 'integer',
        'biggest_word_count' => 'integer',
        'goal_id' => 'integer',
        'story_id' => 'integer',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(JardinoGoal::class, 'goal_id');
    }

    /**
     * Get the progress for this snapshot (current - initial)
     */
    public function getProgressAttribute(): int
    {
        return $this->current_word_count - $this->initial_word_count;
    }

    /**
     * Get the flower-eligible words (biggest - initial)
     */
    public function getFlowerEligibleWordsAttribute(): int
    {
        return $this->biggest_word_count - $this->initial_word_count;
    }

    /**
     * Check if this snapshot is currently active
     */
    public function isActive(): bool
    {
        return $this->deselected_at === null;
    }
}
