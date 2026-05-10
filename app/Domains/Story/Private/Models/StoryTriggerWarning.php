<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('story_trigger_warnings')]
#[Fillable(['story_id', 'story_ref_trigger_warning_id'])]
class StoryTriggerWarning extends Model
{
}
