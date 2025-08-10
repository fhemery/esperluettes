<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Story\Models\Concerns\HasSlugAndOrder;

class StoryRefFeedback extends Model
{
    protected $table = 'story_ref_feedbacks';
    public const HAS_ORDER = true;

    use HasSlugAndOrder;

    protected $fillable = [
        'name', 'slug', 'order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
}
