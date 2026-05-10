<?php

namespace App\Domains\Comment\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('comments')]
#[Fillable(['commentable_type', 'commentable_id', 'author_id', 'body', 'parent_comment_id'])]
class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'commentable_id' => 'integer',
        'author_id' => 'integer',
        'parent_comment_id' => 'integer',
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
