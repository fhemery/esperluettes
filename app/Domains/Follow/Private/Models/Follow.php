<?php

namespace App\Domains\Follow\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('follow_follows', timestamps: false)]
#[Fillable(['follower_id', 'followed_id', 'created_at'])]
class Follow extends Model
{
    protected $casts = ['created_at' => 'datetime'];
}
