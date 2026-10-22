<?php

namespace App\Domains\Fanart\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Fanart\Private\Models\Fanart;

class SoftDeleteFanartsOnUserDeactivated
{
    public function handle(UserDeactivated $event): void
    {
        Fanart::where('user_id', $event->userId)->delete();
    }
}
