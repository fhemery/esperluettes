<?php

namespace App\Domains\Config\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('config_feature_toggles')]
#[Fillable(['domain', 'name', 'access', 'admin_visibility', 'roles', 'updated_by'])]
class FeatureToggle extends Model
{

    protected $casts = [
        'roles' => 'array',
    ];
}
