<?php

namespace App\Domains\StoryRef\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use App\Domains\StoryRef\Private\Models\Concerns\HasSlugAndOrder;

#[Table('story_ref_genres')]
#[Fillable(['name', 'slug', 'description', 'order', 'is_active', 'has_cover'])]
class StoryRefGenre extends Model
{
    public const HAS_ORDER = true;

    use HasSlugAndOrder;

    protected $casts = [
        'is_active' => 'boolean',
        'has_cover' => 'boolean',
        'order' => 'integer',
    ];
}
