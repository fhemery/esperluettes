<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('discord_users')]
#[Fillable(['user_id', 'discord_user_id', 'discord_username'])]
class DiscordUser extends Model
{
    use HasFactory;
}
