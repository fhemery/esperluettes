<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('story_genres')]
#[Fillable(['story_id', 'story_ref_genre_id'])]
class StoryGenre extends Model
{
}
