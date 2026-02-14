<?php

namespace App\Domains\Story\Private\Models;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\StoryGenre;
use App\Domains\Story\Private\Models\StoryTriggerWarning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Story extends Model
{
    use SoftDeletes;
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
        'story_ref_feedback_id',
        'tw_disclosure',
        'is_complete',
        'is_excluded_from_events',
        'cover_type',
        'cover_data',
    ];

    protected $attributes = [
        'cover_type' => self::COVER_DEFAULT,
    ];

    protected $casts = [
        'created_by_user_id' => 'integer',
        'story_ref_type_id' => 'integer',
        'story_ref_audience_id' => 'integer',
        'story_ref_copyright_id' => 'integer',
        'story_ref_feedback_id' => 'integer',
        'reads_logged_total' => 'integer',
        'last_chapter_published_at' => 'datetime',
        'tw_disclosure' => 'string',
        'is_complete' => 'boolean',
        'is_excluded_from_events' => 'boolean',
        'cover_type' => 'string',
    ];

    public const COVER_DEFAULT = 'default';
    public const COVER_THEMED = 'themed';
    public const COVER_CUSTOM = 'custom';

    public static function coverTypeOptions(): array
    {
        return [self::COVER_DEFAULT, self::COVER_THEMED, self::COVER_CUSTOM];
    }

    public const VIS_PUBLIC = 'public';
    public const VIS_COMMUNITY = 'community';
    public const VIS_PRIVATE = 'private';

    public static function visibilityOptions(): array
    {
        return [self::VIS_PUBLIC, self::VIS_COMMUNITY, self::VIS_PRIVATE];
    }

    // Trigger Warning disclosure options
    public const TW_LISTED = 'listed';
    public const TW_NO_TW = 'no_tw';
    public const TW_UNSPOILED = 'unspoiled';

    public static function twDisclosureOptions(): array
    {
        return [self::TW_LISTED, self::TW_NO_TW, self::TW_UNSPOILED];
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
        return $this->hasMany(StoryGenre::class, 'story_id');
    }

    /**
     * Trigger Warnings attached to the story (optional, 0..N)
     */
    public function triggerWarnings()
    {
        return $this->hasMany(StoryTriggerWarning::class, 'story_id');
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

    /**
     * Sum of character_count across published chapters (computed on the fly).
     */
    public function publishedCharacterCount(): int
    {
        return (int) $this->chapters()->published()->sum('character_count');
    }
}
