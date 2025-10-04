<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscordUser extends Model
{
    use HasFactory;

    protected $table = 'discord_users';

    protected $fillable = [
        'user_id',
        'discord_user_id',
        'discord_username',
    ];
}
