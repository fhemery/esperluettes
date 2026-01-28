<?php

namespace App\Domains\Statistics\Private\Models;

use Illuminate\Database\Eloquent\Model;

class StatisticTimeSeries extends Model
{
    protected $table = 'statistic_time_series';

    protected $fillable = [
        'statistic_key',
        'scope_type',
        'scope_id',
        'granularity',
        'period_start',
        'value',
        'cumulative_value',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'cumulative_value' => 'decimal:4',
        'metadata' => 'array',
        'period_start' => 'date',
        'scope_id' => 'integer',
    ];
}
