<?php

namespace App\Domains\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\News\Database\Factories\NewsFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class News extends Model
{
    /** @use HasFactory<\App\Domains\News\Database\Factories\NewsFactory> */
    use HasSlug, HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'header_image_path',
        'is_pinned',
        'display_order',
        'status',
        'meta_description',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'created_by' => 'integer',
        'display_order' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(60)
            ->doNotGenerateSlugsOnUpdate();
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeOrderForListing(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    protected static function newFactory(): NewsFactory
    {
        return NewsFactory::new();
    }
}
