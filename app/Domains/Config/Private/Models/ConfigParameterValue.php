<?php

namespace App\Domains\Config\Private\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigParameterValue extends Model
{
    protected $table = 'config_parameter_values';

    protected $fillable = [
        'domain',
        'key',
        'value',
        'updated_by',
    ];
}
