<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('calendar_quote_contest_categories')]
#[Fillable(['activity_id', 'title', 'description', 'position'])]
class QuoteContestCategory extends Model
{
    public function entries(): HasMany
    {
        return $this->hasMany(QuoteContestEntry::class, 'category_id');
    }
}
