<?php

namespace App\Domains\Story\Private\Console;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Private\Listeners\NotifyOnChapterComment;
use App\Domains\Story\Public\Events\ChapterCommentNotificationsBackfilled;
use App\Domains\Story\Public\Notifications\ChapterCommentNotification;
use Illuminate\Console\Command;

class BackfillChapterCommentNotificationsCommand extends Command
{
    protected $signature = 'story:backfill-chapter-comment-notifications';
    
    protected $description = 'Backfill chapter comment notifications from stored events';

    public function __construct(
        private EventPublicApi $events,
        private NotificationPublicApi $notifications,
        private NotifyOnChapterComment $listener,
        private EventBus $eventBus
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting backfill of chapter comment notifications...');
        
        // Step 1: Delete all existing notifications of this type
        $this->info('Deleting existing notifications...');
        $deletedCount = $this->notifications->deleteNotificationsByType(
            ChapterCommentNotification::type()
        );
        $this->info("Deleted {$deletedCount} existing notifications.");
        
        // Step 2: Query all Comment.Posted events
        $this->info('Fetching Comment.Posted events...');
        $eventDtos = $this->events->getEventsByName('Comment.Posted');
        $this->info("Found " . count($eventDtos) . " events to process.");
        
        // Step 3: Process each event through the listener
        $processed = 0;
        $created = 0;
        $skipped = 0;
        
        foreach ($eventDtos as $dto) {
            $processed++;
            
            // Try to reconstruct the domain event
            $domainEvent = $dto->domainEvent();
            if (!$domainEvent instanceof CommentPosted) {
                $skipped++;
                continue;
            }
            
            // Count notifications before
            $notificationsBefore = $this->notifications->countNotificationsByType(
                ChapterCommentNotification::type()
            );
            
            // Feed to listener (wrapped in try-catch to handle errors gracefully)
            try {
                $this->listener->handle($domainEvent, $dto->occurredAt());
            } catch (\Throwable $e) {
                $this->info("Failed to process event {$domainEvent->name()}: " . $e->getMessage());
                // Skip silently (chapter/story/profile might not exist anymore)
                $skipped++;
                continue;
            }
            
            // Count notifications after
            $notificationsAfter = $this->notifications->countNotificationsByType(
                ChapterCommentNotification::type()
            );
            
            // If a notification was created, increment counter
            if ($notificationsAfter > $notificationsBefore) {
                $created++;
            } else {
                $skipped++;
            }
        }
        
        // Step 4: Report statistics
        $this->newLine();
        $this->info('Backfill completed!');
        $this->info("Processed: {$processed}");
        $this->info("Created: {$created}");
        $this->info("Skipped: {$skipped}");
        
        // Step 5: Emit audit event
        $this->eventBus->emit(new ChapterCommentNotificationsBackfilled(
            eventsProcessed: $processed,
            notificationsCreated: $created,
            notificationsDeleted: $deletedCount,
            skipped: $skipped
        ));
        
        return self::SUCCESS;
    }
}
