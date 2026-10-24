<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Public\Events\UserQuotesRestored;

class RestoreOnUserReactivated
{
    public function __construct(private readonly EventBus $eventBus)
    {
    }

    public function handle(UserReactivated $event): void
    {
        // Counted explicitly: restore() return values differ between SQLite and MySQL.
        $count = Quote::onlyTrashed()->where('user_id', $event->userId)->count();

        Quote::withTrashed()->where('user_id', $event->userId)->restore();

        if ($count > 0) {
            $this->eventBus->emit(new UserQuotesRestored($event->userId, $count));
        }
    }
}
