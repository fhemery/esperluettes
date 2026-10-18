<?php

namespace App\Domains\Fanart\Private\Models;

use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domains\Fanart\Private\Support\FanartViewer;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Soft-deleted only by user deactivation; every other removal is a hard delete.
 */
#[Table('fanarts')]
#[Fillable([
    'story_id', 'chapter_id', 'user_id', 'kind', 'status', 'is_artwork', 'title', 'description',
    'file_path', 'share_on_discord', 'discord_sent_at', 'published_at',
])]
class Fanart extends Model
{
    use SoftDeletes;

    protected $casts = [
        'story_id' => 'integer',
        'chapter_id' => 'integer',
        'user_id' => 'integer',
        'kind' => FanartKind::class,
        'status' => FanartStatus::class,
        'is_artwork' => 'boolean',
        'share_on_discord' => 'boolean',
        'discord_sent_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Keeps only what the viewer may see, in the query itself: readers get published fanarts
     * of published (or no) chapters; authors and staff get everything.
     */
    public function scopeVisibleTo(Builder $query, FanartViewer $viewer): Builder
    {
        if ($viewer->seesAll) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($viewer) {
            $visible->where(function (Builder $published) use ($viewer) {
                $published->where('status', FanartStatus::Published)
                    ->where(fn (Builder $chapter) => $chapter->whereNull('chapter_id')
                        ->orWhereIn('chapter_id', $viewer->publishedChapterIds));
            });

            if ($viewer->includeOwnPending) {
                $visible->orWhere(fn (Builder $own) => $own->where('status', FanartStatus::Pending)
                    ->where('user_id', $viewer->userId));
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FanartStatus::Published);
    }
}
