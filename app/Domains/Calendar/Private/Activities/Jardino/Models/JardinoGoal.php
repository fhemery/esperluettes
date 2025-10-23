<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Models;

use Illuminate\Database\Eloquent\Model;

class JardinoGoal extends Model
{
    protected $table = 'calendar_jardino_goals';

    protected $fillable = [
        'activity_id',
        'user_id',
        'story_id',
        'target_word_count',
    ];
}
