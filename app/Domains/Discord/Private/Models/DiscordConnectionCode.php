<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('discord_connection_codes')]
#[Fillable(['user_id', 'code', 'expires_at', 'used_at'])]
class DiscordConnectionCode extends Model
{
    use HasFactory;

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
