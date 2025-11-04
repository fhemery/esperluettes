<?php

namespace App\Domains\Notification\Private\Console;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Private\Services\NotificationService;
use App\Domains\Notification\Public\Events\NotificationsCleanedUp;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Console\Command;

class CleanupOldNotificationsCommand extends Command
{
    protected $signature = 'notifications:cleanup';

    protected $description = 'Clean up old notifications (>30 days) and notifications with unknown types';

    public function __construct(
        private NotificationFactory $factory,
        private NotificationService $service,
        private EventBus $eventBus
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Get the cutoff date (30 days ago)
        $cutoffDate = now()->subDays(30);

        // Delete old notifications via service
        $oldDeleted = $this->service->deleteOlderThan($cutoffDate);

        // Get all registered types and delete unknown ones
        $registeredTypes = $this->factory->getRegisteredTypes();
        $unknownDeleted = $this->service->deleteUnknownTypes($registeredTypes);

        // Emit event via EventBus for central system
        $this->eventBus->emit(new NotificationsCleanedUp($oldDeleted, $unknownDeleted));

        // Output results
        $this->info("Deleted {$oldDeleted} old notifications (>30 days)");
        $this->info("Deleted {$unknownDeleted} notifications with unknown types");
        $this->info("Total: " . ($oldDeleted + $unknownDeleted) . " notifications cleaned up");

        return self::SUCCESS;
    }
}
