<?php

namespace App\Domains\Fanart\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * A request for the fanartiste role. Accepted requests are deleted (the grant
 * row is the record); `declined` rows feed the 30-day cooldown.
 */
#[Table('fanart_role_requests')]
#[Fillable(['story_id', 'user_id', 'status', 'decided_by_user_id', 'decided_at'])]
class FanartRoleRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DECLINED = 'declined';

    protected $casts = [
        'story_id' => 'integer',
        'user_id' => 'integer',
        'decided_by_user_id' => 'integer',
        'decided_at' => 'datetime',
    ];
}
