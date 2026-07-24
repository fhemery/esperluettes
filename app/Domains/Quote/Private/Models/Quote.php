<?php

namespace App\Domains\Quote\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('quotes')]
#[Fillable(['user_id', 'chapter_id', 'story_id', 'highlighted_text', 'prefix', 'suffix', 'note'])]
class Quote extends Model
{
    use SoftDeletes;

    protected $casts = [
        'user_id' => 'integer',
        'chapter_id' => 'integer',
        'story_id' => 'integer',
    ];
}
