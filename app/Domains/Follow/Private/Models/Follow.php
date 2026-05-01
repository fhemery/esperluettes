<?php

namespace App\Domains\Follow\Private\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $table = 'follow_follows';

    public $timestamps = false;

    protected $fillable = ['follower_id', 'followed_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
