<?php

namespace App\Domains\ReadList\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('read_list_entries')]
#[Fillable(['user_id', 'story_id'])]
class ReadListEntry extends Model
{

    protected $casts = [
        'user_id' => 'integer',
        'story_id' => 'integer',
    ];
}
