<?php

namespace App\Domains\Quote\Public\Providers;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Private\Listeners\NullifyUserOnUserDeleted;
use App\Domains\Quote\Private\Listeners\RestoreOnUserReactivated;
use App\Domains\Quote\Private\Listeners\SoftDeleteOnUserDeactivated;
use Illuminate\Support\ServiceProvider;

class QuoteServiceProvider extends ServiceProvider
{
    public const TAB_QUOTE = 'quote';
    public const KEY_BOOK_PUBLIC = 'book_public';

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Quote/Database/Migrations'));

        $eventBus = app(EventBus::class);
        $eventBus->subscribe(UserDeleted::class, [app(NullifyUserOnUserDeleted::class), 'handle']);
        $eventBus->subscribe(UserDeactivated::class, [app(SoftDeleteOnUserDeactivated::class), 'handle']);
        $eventBus->subscribe(UserReactivated::class, [app(RestoreOnUserReactivated::class), 'handle']);
    }
}
