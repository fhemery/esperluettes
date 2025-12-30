<?php

namespace App\Domains\Settings\Private\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'user_id',
        'domain',
        'key',
        'value',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];
}
