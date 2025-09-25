<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Shared\Contracts\Sortable;

class Chapter extends Model implements Sortable
{
    protected $table = 'story_chapters';

    protected $fillable = [
        'story_id',
        'title',
        'slug',
        'author_note',
        'content',
        'sort_order',
        'status',
        'first_published_at',
        'reads_logged_count',
        'word_count',
        'character_count',
    ];

    protected $casts = [
        'story_id' => 'integer',
        'sort_order' => 'integer',
        'reads_logged_count' => 'integer',
        'first_published_at' => 'datetime',
        'last_edited_at' => 'datetime',
        'word_count' => 'integer',
        'character_count' => 'integer',
    ];

    public const STATUS_NOT_PUBLISHED = 'not_published';
    public const STATUS_PUBLISHED = 'published';

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function getSlugWithIdAttribute(): string
    {
        return $this->slug; // stored with id suffix
    }

    // Sortable contract
    public function getId(): int { return (int)$this->id; }
    public function getSortOrder(): int { return (int)$this->sort_order; }
    public function setSortOrder(int $order): void { $this->sort_order = $order; }
}
