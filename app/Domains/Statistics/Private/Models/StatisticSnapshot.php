<?php

namespace App\Domains\Statistics\Private\Models;

use Illuminate\Database\Eloquent\Model;

class StatisticSnapshot extends Model
{
    protected $table = 'statistic_snapshots';

    protected $fillable = [
        'statistic_key',
        'scope_type',
        'scope_id',
        'value',
        'metadata',
        'computed_at',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'metadata' => 'array',
        'computed_at' => 'datetime',
        'scope_id' => 'integer',
    ];
}
