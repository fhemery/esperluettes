<?php

namespace App\Domains\Config\Private\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureToggle extends Model
{
    protected $table = 'config_feature_toggles';

    protected $fillable = [
        'domain',
        'name',
        'access',
        'admin_visibility',
        'roles',
        'updated_by',
    ];

    protected $casts = [
        'roles' => 'array',
    ];
}
