<?php

namespace App\Domains\FAQ\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class FaqQuestion extends Model
{
    use HasSlug;

    protected $fillable = [
        'faq_category_id',
        'question',
        'slug',
        'answer',
        'image_path',
        'image_alt_text',
        'sort_order',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'faq_category_id' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('question')
            ->saveSlugsTo('slug');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
