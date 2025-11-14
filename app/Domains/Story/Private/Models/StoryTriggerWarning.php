<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Model;

class StoryTriggerWarning extends Model
{
    protected $table = 'story_trigger_warnings';

    public $timestamps = true;

    protected $fillable = [
        'story_id',
        'story_ref_trigger_warning_id',
    ];
}
