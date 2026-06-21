<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Story\Private\Models\ReadingProgress;
use App\Domains\Shared\Contracts\Sortable;

#[Table('story_chapters')]
#[Fillable(['story_id', 'title', 'slug', 'author_note', 'content', 'sort_order', 'status', 'first_published_at', 'publish_at', 'reads_logged_count', 'word_count', 'character_count'])]
class Chapter extends Model implements Sortable
{
    use SoftDeletes;

    protected $casts = [
        'story_id' => 'integer',
        'sort_order' => 'integer',
        'reads_logged_count' => 'integer',
        'first_published_at' => 'datetime',
        'publish_at' => 'datetime',
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

    /**
     * Reading progress entries for this chapter.
     */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class, 'chapter_id');
    }

   

    // Sortable contract
    public function getId(): int { return (int)$this->id; }
    public function getSortOrder(): int { return (int)$this->sort_order; }
    public function setSortOrder(int $order): void { $this->sort_order = $order; }
    public function getIsRead(): ?bool {
        if ($this->is_read === null) {
            return null;
        }
        return ($this->is_read ?? 0) > 0;
    }
}
