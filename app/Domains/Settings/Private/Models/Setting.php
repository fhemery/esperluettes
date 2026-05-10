<?php

namespace App\Domains\Settings\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('settings')]
#[Fillable(['user_id', 'domain', 'key', 'value'])]
class Setting extends Model
{

    protected $casts = [
        'user_id' => 'integer',
    ];
}
