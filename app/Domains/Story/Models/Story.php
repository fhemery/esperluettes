<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Domains\Auth\Models\User;

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

    public function authors()
    {
        return $this->belongsToMany(User::class, 'story_collaborators', 'story_id', 'user_id')
            ->withPivot(['role', 'invited_by_user_id', 'invited_at', 'accepted_at'])
            ->wherePivot('role', 'author');
    }
}
