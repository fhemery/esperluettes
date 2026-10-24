<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Public\Events\UserQuotesRemoved;
use Illuminate\Support\Facades\DB;

class DeleteQuotesOnUserDeleted
{
    public function __construct(private readonly EventBus $eventBus)
    {
    }

    public function handle(UserDeleted $event): void
    {
        // Visible rows only: rows hidden by a prior deactivation were already reported.
        $count = Quote::where('user_id', $event->userId)->count();

        // Raw delete: bypasses the soft-delete scope, so rows already soft-deleted
        // by a prior deactivation are removed too. No orphan quote row can remain.
        DB::table('quotes')->where('user_id', $event->userId)->delete();

        if ($count > 0) {
            $this->eventBus->emit(new UserQuotesRemoved($event->userId, $count));
        }
    }
}
