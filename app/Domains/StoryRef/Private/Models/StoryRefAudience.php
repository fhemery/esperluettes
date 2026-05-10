<?php

namespace App\Domains\StoryRef\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use App\Domains\StoryRef\Private\Models\Concerns\HasSlugAndOrder;

#[Table('story_ref_audiences')]
#[Fillable(['name', 'slug', 'order', 'is_active', 'threshold_age', 'is_mature_audience'])]
class StoryRefAudience extends Model
{
    public const HAS_ORDER = true;

    use HasSlugAndOrder;

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'threshold_age' => 'integer',
        'is_mature_audience' => 'boolean',
    ];
}
