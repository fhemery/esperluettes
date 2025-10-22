<?php

namespace App\Domains\FAQ\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class FaqCategory extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FaqQuestion::class);
    }
}
