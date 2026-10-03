<?php

namespace App\Domains\Comment\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('comment_annotations')]
#[Fillable([
    'comment_id',
    'parent_annotation_id',
    'author_id',
    'body',
    'highlighted_text',
    'prefix',
    'suffix',
    'is_processed',
    'processed_at',
])]
class CommentAnnotation extends Model
{
    use SoftDeletes;

    protected $casts = [
        'comment_id' => 'integer',
        'parent_annotation_id' => 'integer',
        'author_id' => 'integer',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_annotation_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_annotation_id');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_annotation_id');
    }

    public function scopeRepliesOnly(Builder $query): Builder
    {
        return $query->whereNotNull('parent_annotation_id');
    }
}
