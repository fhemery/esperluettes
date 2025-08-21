<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id',
        'title',
        'slug',
        'description',
        'visibility',
        'last_chapter_published_at',
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

}
