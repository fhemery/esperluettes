<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use Illuminate\Support\Facades\DB;

class DeleteQuotesOnUserDeleted
{
    public function handle(UserDeleted $event): void
    {
        // Raw delete: bypasses the soft-delete scope, so rows already soft-deleted
        // by a prior deactivation are removed too. No orphan quote row can remain.
        DB::table('quotes')->where('user_id', $event->userId)->delete();
    }
}
