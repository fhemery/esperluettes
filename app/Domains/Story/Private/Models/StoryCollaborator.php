<?php

namespace App\Domains\Story\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('story_collaborators', timestamps: false)]
#[WithoutIncrementing]
#[Fillable(['story_id', 'user_id', 'role', 'invited_by_user_id', 'invited_at', 'accepted_at'])]
class StoryCollaborator extends Model
{
    protected $primaryKey = null;

    protected $casts = [
        'story_id' => 'integer',
        'user_id' => 'integer',
        'invited_by_user_id' => 'integer',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Scope to get only authors
     */
    public function scopeAuthors($query)
    {
        return $query->where('role', 'author');
    }

    /**
     * Check if this collaborator is an author
     */
    public function isAuthor(): bool
    {
        return $this->role === 'author';
    }
}
