<?php

namespace App\Domains\Comment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'comments';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'author_id',
        'body',
        'parent_comment_id',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_comment_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_comment_id');
    }
}
