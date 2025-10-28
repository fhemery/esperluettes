<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JardinoGardenCell extends Model
{
    protected $table = 'calendar_jardino_garden_cells';

    protected $fillable = [
        'activity_id',
        'x',
        'y',
        'type',
        'flower_image',
        'user_id',
        'planted_at',
    ];

    protected $casts = [
        'planted_at' => 'datetime',
        'x' => 'integer',
        'y' => 'integer',
        'user_id' => 'integer',
        'activity_id' => 'integer',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(JardinoGoal::class, 'activity_id', 'activity_id');
    }
}
