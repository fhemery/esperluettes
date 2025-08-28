<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    protected $fillable = [
        'story_id',
        'title',
        'slug',
        'author_note',
        'content',
        'sort_order',
        'status',
        'first_published_at',
        'reads_guest_count',
        'reads_logged_count',
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
}
