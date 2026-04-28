<?php

namespace App\Domains\Notification\Private\Repositories;

use App\Domains\Notification\Private\Models\NotificationPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationPreferencesRepository
{
    /** Returns all stored preferences for a user, keyed by "type##channel". */
    public function getForUser(int $userId): Collection
    {
        return DB::table('notification_preferences')
            ->where('user_id', $userId)
            ->get()
            ->keyBy(fn($row) => $row->type . '##' . $row->channel);
    }

    public function setPreference(int $userId, string $type, string $channel, bool $enabled): void
    {
        NotificationPreference::updateOrCreate(
            ['user_id' => $userId, 'type' => $type, 'channel' => $channel],
            ['enabled' => $enabled],
        );
    }

    public function deletePreference(int $userId, string $type, string $channel): void
    {
        DB::table('notification_preferences')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('channel', $channel)
            ->delete();
    }

    /** Bulk upsert: set enabled = $enabled for all given types on the given channel. */
    public function setForUserAndChannel(int $userId, string $channel, bool $enabled, array $types): void
    {
        if (empty($types)) {
            return;
        }

        $now = now();
        $rows = array_map(fn($type) => [
            'user_id'    => $userId,
            'type'       => $type,
            'channel'    => $channel,
            'enabled'    => $enabled,
            'created_at' => $now,
            'updated_at' => $now,
        ], $types);

        DB::table('notification_preferences')->upsert(
            $rows,
            ['user_id', 'type', 'channel'],
            ['enabled', 'updated_at'],
        );
    }

    /** Bulk delete: remove stored preferences for the given types on the given channel. */
    public function deleteForUserAndChannel(int $userId, string $channel, array $types): void
    {
        if (empty($types)) {
            return;
        }

        DB::table('notification_preferences')
            ->where('user_id', $userId)
            ->where('channel', $channel)
            ->whereIn('type', $types)
            ->delete();
    }

    /**
     * Returns the subset of $userIds who should receive on $channel.
     * For default-ON channels: removes opted-out users.
     * For default-OFF channels: keeps only opted-in users.
     *
     * @param int[] $userIds
     * @return int[]
     */
    public function filterForChannel(array $userIds, string $type, string $channel, bool $defaultEnabled): array
    {
        if (empty($userIds)) {
            return [];
        }

        if ($defaultEnabled) {
            $optedOut = DB::table('notification_preferences')
                ->where('type', $type)
                ->where('channel', $channel)
                ->where('enabled', false)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->map(fn($v) => (int) $v)
                ->all();

            return array_values(array_diff($userIds, $optedOut));
        }

        return DB::table('notification_preferences')
            ->where('type', $type)
            ->where('channel', $channel)
            ->where('enabled', true)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    /**
     * Returns all opted-in user IDs for a type+channel.
     * Used for default-OFF channels at broadcast time to avoid a massive IN clause.
     *
     * @return int[]
     */
    public function getOptedInUserIds(string $type, string $channel): array
    {
        return DB::table('notification_preferences')
            ->where('type', $type)
            ->where('channel', $channel)
            ->where('enabled', true)
            ->pluck('user_id')
            ->map(fn($v) => (int) $v)
            ->all();
    }
}
