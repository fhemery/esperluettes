<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use Illuminate\Support\Facades\DB;

class NullifyUserOnUserDeleted
{
    public function handle(UserDeleted $event): void
    {
        DB::table('quotes')->where('user_id', $event->userId)->update(['user_id' => null]);
    }
}
