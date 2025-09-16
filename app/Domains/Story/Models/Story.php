<?php

namespace App\Domains\Story\Models;

use App\Domains\StoryRef\Models\StoryRefGenre;
use App\Domains\StoryRef\Models\StoryRefTriggerWarning;
use App\Domains\Story\Models\Chapter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Story extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'title',
        'slug',
        'description',
        'visibility',
        'last_chapter_published_at',
        'reads_logged_total',
        'story_ref_type_id',
        'story_ref_audience_id',
        'story_ref_copyright_id',
        'story_ref_feedback_id'
    ];

    protected $casts = [
        'created_by_user_id' => 'integer',
        'story_ref_type_id' => 'integer',
        'story_ref_audience_id' => 'integer',
        'story_ref_copyright_id' => 'integer',
        'story_ref_feedback_id' => 'integer',
        'reads_logged_total' => 'integer',
        'last_chapter_published_at' => 'datetime',
    ];

    public const VIS_PUBLIC = 'public';
    public const VIS_COMMUNITY = 'community';
    public const VIS_PRIVATE = 'private';

    public static function visibilityOptions(): array
    {
        return [self::VIS_PUBLIC, self::VIS_COMMUNITY, self::VIS_PRIVATE];
    }

    public static function generateSlugBase(string $title): string
    {
        return Str::slug($title);
    }

    public function getSlugWithIdAttribute(): string
    {
        return $this->slug; // stored with id suffix
    }

    /**
     * Relationship to all collaborators
     */
    public function collaborators()
    {
        return $this->hasMany(StoryCollaborator::class);
    }

    /**
     * Get only author collaborators
     */
    public function authors()
    {
        return $this->hasMany(StoryCollaborator::class)->authors();
    }

    public function isCollaborator(?int $userId): bool
    {
        return $this->collaborators()->pluck('user_id')->contains($userId);
    }

    /**
     * Check if the given user id is an author collaborator for this story.
     */
    public function isAuthor(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return $this->authors()->where('user_id', $userId)->exists();
    }

    /**
     * Genres attached to the story (1..3 required by validation)
     */
    public function genres()
    {
        return $this->belongsToMany(StoryRefGenre::class, 'story_genres', 'story_id', 'story_ref_genre_id')
            ->withTimestamps();
    }

    /**
     * Trigger Warnings attached to the story (optional, 0..N)
     */
    public function triggerWarnings()
    {
        return $this->belongsToMany(StoryRefTriggerWarning::class, 'story_trigger_warnings', 'story_id', 'story_ref_trigger_warning_id')
            ->withTimestamps();
    }

    /**
     * Chapters belonging to this story
     */
    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * Sum of word_count across published chapters (computed on the fly).
     */
    public function publishedWordCount(): int
    {
        return (int) $this->chapters()->published()->sum('word_count');
    }
}
