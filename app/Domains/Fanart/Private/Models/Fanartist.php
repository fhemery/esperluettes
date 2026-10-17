<?php

namespace App\Domains\Fanart\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * Fanartiste membership of a story. Composite natural key (story_id, user_id),
 * so there is no single primary key.
 */
#[Table('fanart_fanartists', timestamps: false)]
#[WithoutIncrementing]
#[Fillable(['story_id', 'user_id', 'granted_by_user_id'])]
class Fanartist extends Model
{
    public const CREATED_AT = 'created_at';

    protected $primaryKey = null;

    protected $casts = [
        'story_id' => 'integer',
        'user_id' => 'integer',
        'granted_by_user_id' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_at ??= now();
        });
    }
}
