<?php

namespace App\Domains\Fanart\Private\Listeners;

use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Fanart\Private\Models\Fanart;

class RestoreFanartsOnUserReactivated
{
    public function handle(UserReactivated $event): void
    {
        Fanart::withTrashed()->where('user_id', $event->userId)->restore();
    }
}
