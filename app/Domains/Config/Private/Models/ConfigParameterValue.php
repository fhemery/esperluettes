<?php

namespace App\Domains\Config\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('config_parameter_values')]
#[Fillable(['domain', 'key', 'value', 'updated_by'])]
class ConfigParameterValue extends Model
{
}
