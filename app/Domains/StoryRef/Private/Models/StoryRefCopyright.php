<?php

namespace App\Domains\StoryRef\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use App\Domains\StoryRef\Private\Models\Concerns\HasSlugAndOrder;

#[Table('story_ref_copyrights')]
#[Fillable(['name', 'slug', 'description', 'order', 'is_active'])]
class StoryRefCopyright extends Model
{
    public const HAS_ORDER = true;

    use HasSlugAndOrder;

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
}
