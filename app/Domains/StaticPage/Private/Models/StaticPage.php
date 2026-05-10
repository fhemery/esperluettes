<?php

namespace App\Domains\StaticPage\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\StaticPage\Database\Factories\StaticPageFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('static_pages')]
#[Fillable(['title', 'slug', 'summary', 'content', 'header_image_path', 'status', 'meta_description', 'published_at', 'created_by'])]
class StaticPage extends Model
{
    use HasSlug, HasFactory;

    protected $casts = [
        'published_at' => 'datetime',
        'created_by' => 'integer',
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

    protected static function newFactory(): StaticPageFactory
    {
        return StaticPageFactory::new();
    }
}
