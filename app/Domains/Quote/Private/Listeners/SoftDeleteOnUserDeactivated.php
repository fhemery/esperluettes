<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Quote\Private\Models\Quote;

class SoftDeleteOnUserDeactivated
{
    public function handle(UserDeactivated $event): void
    {
        Quote::where('user_id', $event->userId)->delete();
    }
}
