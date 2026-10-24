<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Quote\Public\Events\UserQuotesRemoved;

class SoftDeleteOnUserDeactivated
{
    public function __construct(private readonly EventBus $eventBus)
    {
    }

    public function handle(UserDeactivated $event): void
    {
        $query = Quote::where('user_id', $event->userId);
        $count = (clone $query)->count();

        $query->delete();

        if ($count > 0) {
            $this->eventBus->emit(new UserQuotesRemoved($event->userId, $count));
        }
    }
}
