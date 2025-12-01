<?php

namespace App\Domains\StoryRef\Private\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\StoryRef\Private\Models\Concerns\HasSlugAndOrder;

class StoryRefAudience extends Model
{
    protected $table = 'story_ref_audiences';
    public const HAS_ORDER = true;

    use HasSlugAndOrder;

    protected $fillable = [
        'name', 'slug', 'order', 'is_active', 'threshold_age', 'is_mature_audience',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'threshold_age' => 'integer',
        'is_mature_audience' => 'boolean',
    ];
}
