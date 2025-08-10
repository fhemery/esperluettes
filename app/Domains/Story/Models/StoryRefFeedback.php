<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;

class StoryRefFeedback extends Model
{
    protected $table = 'story_ref_feedbacks';

    protected $fillable = [
        'name', 'slug', 'order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
}
