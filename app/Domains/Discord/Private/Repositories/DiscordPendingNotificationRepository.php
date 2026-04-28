<?php

namespace App\Domains\Discord\Private\Repositories;

use App\Domains\Discord\Private\Models\DiscordPendingNotification;
use App\Domains\Discord\Private\Models\DiscordPendingRecipient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DiscordPendingNotificationRepository
{
    /**
     * Returns pending notifications that have at least one unsent recipient,
     * ordered by created_at ascending. Each result has recipients (unsent only)
     * eager-loaded.
     */
    public function getPendingWithRecipients(int $perPage, int $page): LengthAwarePaginator
    {
        return DiscordPendingNotification::query()
            ->whereHas('recipients', fn ($q) => $q->whereNull('sent_at'))
            ->with([
                'recipients' => fn ($q) => $q->whereNull('sent_at')
                    ->select(['pending_notification_id', 'discord_id']),
            ])
            ->orderBy('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function createPending(int $notificationId): DiscordPendingNotification
    {
        return DiscordPendingNotification::create([
            'notification_id' => $notificationId,
        ]);
    }

    /**
     * @param array<array{user_id: int, discord_id: string}> $recipients
     */
    public function createRecipients(int $pendingId, array $recipients): void
    {
        if (empty($recipients)) {
            return;
        }

        $now  = now();
        $rows = [];
        foreach ($recipients as $r) {
            $rows[] = [
                'pending_notification_id' => $pendingId,
                'user_id'                 => (int) $r['user_id'],
                'discord_id'              => (string) $r['discord_id'],
                'sent_at'                 => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ];
        }

        DB::table('discord_pending_recipients')->insert($rows);
    }

    /**
     * Mark all unsent recipients of a pending notification as delivered.
     * Returns the number of rows updated.
     */
    public function markAllRecipientsDelivered(int $pendingNotificationId): int
    {
        return DiscordPendingRecipient::query()
            ->where('pending_notification_id', $pendingNotificationId)
            ->whereNull('sent_at')
            ->update(['sent_at' => now()]);
    }

    /**
     * Mark all unsent recipients as delivered EXCEPT the given discord IDs.
     * Returns the number of rows updated.
     *
     * @param string[] $failedDiscordIds
     */
    public function markRecipientsDeliveredExcept(int $pendingNotificationId, array $failedDiscordIds): int
    {
        $query = DiscordPendingRecipient::query()
            ->where('pending_notification_id', $pendingNotificationId)
            ->whereNull('sent_at');

        if (!empty($failedDiscordIds)) {
            $query->whereNotIn('discord_id', $failedDiscordIds);
        }

        return $query->update(['sent_at' => now()]);
    }

    /**
     * Delete all pending recipient rows for a website user (called on Discord disconnect or user deletion).
     */
    public function deleteRecipientsForUser(int $userId): void
    {
        DiscordPendingRecipient::query()
            ->where('user_id', $userId)
            ->delete();
    }
}
