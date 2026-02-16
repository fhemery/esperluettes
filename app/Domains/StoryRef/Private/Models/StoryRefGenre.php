<?php

namespace App\Domains\StoryRef\Private\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\StoryRef\Private\Models\Concerns\HasSlugAndOrder;

class StoryRefGenre extends Model
{
    protected $table = 'story_ref_genres';
    public const HAS_ORDER = true;

    use HasSlugAndOrder;

    protected $fillable = [
        'name', 'slug', 'description', 'order', 'is_active', 'has_cover',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_cover' => 'boolean',
        'order' => 'integer',
    ];
}
