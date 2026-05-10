<?php

namespace App\Domains\Notification\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('notifications')]
#[Fillable(['source_user_id', 'content_key', 'content_data', 'created_at', 'updated_at'])]
class Notification extends Model
{

    protected $casts = [
        'content_data' => 'array',
    ];
}
