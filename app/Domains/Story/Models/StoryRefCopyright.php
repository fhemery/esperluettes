<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;

class StoryRefCopyright extends Model
{
    protected $table = 'story_ref_copyrights';

    protected $fillable = [
        'name', 'slug', 'description', 'order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
}
