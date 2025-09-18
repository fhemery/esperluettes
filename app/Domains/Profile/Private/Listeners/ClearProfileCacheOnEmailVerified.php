<?php

namespace App\Domains\Profile\Private\Listeners;

use App\Domains\Auth\Events\EmailVerified;
use App\Domains\Profile\Private\Services\ProfileCacheService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ClearProfileCacheOnEmailVerified implements ShouldHandleEventsAfterCommit
{
    public function __construct(private ProfileCacheService $cache)
    {
    }

    public function handle(EmailVerified $event): void
    {
        $this->cache->forgetByUserId($event->userId);
    }
}
