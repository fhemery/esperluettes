<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Model;

class StoryGenre extends Model
{
    protected $table = 'story_genres';

    public $timestamps = true;

    protected $fillable = [
        'story_id',
        'story_ref_genre_id',
    ];
}
