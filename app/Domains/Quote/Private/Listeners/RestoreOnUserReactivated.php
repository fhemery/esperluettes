<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Quote\Private\Models\Quote;

class RestoreOnUserReactivated
{
    public function handle(UserReactivated $event): void
    {
        Quote::withTrashed()->where('user_id', $event->userId)->restore();
    }
}
