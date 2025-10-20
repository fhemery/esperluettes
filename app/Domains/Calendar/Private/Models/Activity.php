<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\CarbonImmutable;
use App\Domains\Calendar\Public\Contracts\ActivityState;

class Activity extends Model
{
    protected $table = 'calendar_activities';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'activity_type',
        'role_restrictions',
        'requires_subscription',
        'max_participants',
        'preview_starts_at',
        'active_starts_at',
        'active_ends_at',
        'archived_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'role_restrictions' => 'array',
        'requires_subscription' => 'boolean',
        'preview_starts_at' => 'datetime',
        'active_starts_at' => 'datetime',
        'active_ends_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function state(): Attribute
    {
        return Attribute::get(function () {
            $now = CarbonImmutable::now();
            if (!$this->preview_starts_at || $this->preview_starts_at->greaterThan($now)) {
                return ActivityState::DRAFT;
            }
            if ($this->archived_at && $this->archived_at->lessThanOrEqualTo($now)) {
                return ActivityState::ARCHIVED;
            }
            if ($this->active_ends_at && $this->active_ends_at->lessThanOrEqualTo($now)) {
                return ActivityState::ENDED;
            }
            if (!$this->active_starts_at || $this->active_starts_at->greaterThan($now)) {
                return ActivityState::PREVIEW;
            }
            return ActivityState::ACTIVE;
        });
    }
}
