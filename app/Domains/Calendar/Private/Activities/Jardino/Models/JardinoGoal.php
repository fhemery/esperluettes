<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected $casts = [
        'target_word_count' => 'integer',
    ];

    public function storySnapshots(): HasMany
    {
        return $this->hasMany(JardinoStorySnapshot::class, 'goal_id');
    }

    public function currentStorySnapshot(): HasOne
    {
        return $this->hasOne(JardinoStorySnapshot::class, 'goal_id')->whereNull('deselected_at');
    }

    public function gardenCells(): HasMany
    {
        return $this->hasMany(JardinoGardenCell::class, 'activity_id', 'activity_id');
    }

    public function plantedFlowers(): HasMany
    {
        return $this->gardenCells()->where('type', 'flower')->where('user_id', $this->user_id);
    }
}
