<?php

namespace App\Domains\Story\Models;

use Illuminate\Database\Eloquent\Model;

class StoryRefGenre extends Model
{
    protected $table = 'story_ref_genres';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
