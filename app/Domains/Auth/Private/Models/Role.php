<?php

namespace App\Domains\Auth\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Domains\Auth\Private\Models\User;

#[Fillable(['name', 'slug', 'description'])]
class Role extends Model
{

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
