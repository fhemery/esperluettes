<?php

namespace App\Domains\Fanart\Private\Models;

use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Soft-deleted only by user deactivation; every other removal is a hard delete.
 */
#[Table('fanarts')]
#[Fillable([
    'story_id', 'chapter_id', 'user_id', 'kind', 'status', 'is_artwork', 'title', 'description',
    'file_path', 'share_on_discord', 'discord_sent_at', 'published_at',
])]
class Fanart extends Model
{
    use SoftDeletes;

    protected $casts = [
        'story_id' => 'integer',
        'chapter_id' => 'integer',
        'user_id' => 'integer',
        'kind' => FanartKind::class,
        'status' => FanartStatus::class,
        'is_artwork' => 'boolean',
        'share_on_discord' => 'boolean',
        'discord_sent_at' => 'datetime',
        'published_at' => 'datetime',
    ];
}
