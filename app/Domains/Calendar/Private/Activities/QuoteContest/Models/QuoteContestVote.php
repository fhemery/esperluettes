<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('calendar_quote_contest_votes')]
#[Fillable(['category_id', 'entry_id', 'user_id'])]
class QuoteContestVote extends Model
{
}
