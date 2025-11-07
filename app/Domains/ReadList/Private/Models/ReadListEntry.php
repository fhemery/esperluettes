<?php

namespace App\Domains\ReadList\Private\Models;

use Illuminate\Database\Eloquent\Model;

class ReadListEntry extends Model
{
    protected $table = 'read_list_entries';

    protected $fillable = [
        'user_id',
        'story_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'story_id' => 'integer',
    ];
}
