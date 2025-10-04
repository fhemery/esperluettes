<?php

namespace App\Domains\Discord\Private\Services;

use App\Domains\Discord\Private\Models\DiscordConnectionCode;
use App\Domains\Discord\Private\Models\DiscordUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DiscordAuthService
{
    /**
     * Generate and persist a one-time connection code, returning the code string.
     */
    public function generateConnectionCodeForUser(int $userId): string
    {
        $code = strtolower(bin2hex(random_bytes(4))); // 8 hex chars

        $now = CarbonImmutable::now();
        $expiresAt = $now->addMinutes(5);

        DB::transaction(function () use ($userId, $code, $expiresAt) {
            // Optionally clean up expired codes for all users
            // and all other unused codes for current user
            DiscordConnectionCode::query()
                ->where(function ($q) {
                    $q->whereNull('used_at')
                      ->where('expires_at', '<', CarbonImmutable::now());
                })
                ->orWhere(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->whereNull('used_at');
                })
                ->delete();

            DiscordConnectionCode::create([
                'user_id' => $userId,
                'code' => $code,
                'expires_at' => $expiresAt,
            ]);
        });

        return $code;
    }

    /**
     * Consume a valid, non-expired, unused code and return the associated user id.
     * Returns null if invalid/expired/used.
     */
    public function consumeValidCode(string $code): ?int
    {
        $now = CarbonImmutable::now();

        /** @var DiscordConnectionCode|null $row */
        $row = DiscordConnectionCode::query()
            ->where('code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now)
            ->first();

        if (!$row) {
            return null;
        }

        DB::transaction(function () use ($row) {
            // Mark as used
            $row->used_at = CarbonImmutable::now();
            $row->save();
        });

        return (int) $row->user_id;
    }

    /**
     * Link the provided Discord user to the given user id.
     * Returns a structured result:
     *   ['success' => true]
     *   ['success' => false, 'reason' => 'user_already_linked'] when the current user already has a different discord id
     *   ['success' => false, 'reason' => 'discord_id_taken'] when this discord id is linked to another user
     */
    public function linkDiscordUser(int $userId, string $discordId, string $discordUsername): array
    {
        // If this user is already linked to a different Discord ID, conflict
        $current = DiscordUser::query()->where('user_id', $userId)->first();
        if ($current && (string) $current->discord_user_id !== (string) $discordId) {
            return ['success' => false, 'reason' => 'user_already_linked'];
        }

        $existing = DiscordUser::query()->where('discord_user_id', $discordId)->first();
        if ($existing && (int) $existing->user_id !== (int) $userId) {
            return ['success' => false, 'reason' => 'discord_id_taken'];
        }

        DiscordUser::query()->updateOrCreate(
            ['discord_user_id' => $discordId],
            ['user_id' => $userId, 'discord_username' => $discordUsername]
        );

        return ['success' => true];
    }

    /**
     * Return the website user id mapped to the given discord user id, or null if none.
     */
    public function getUserIdByDiscordId(string $discordId): ?int
    {
        $record = DiscordUser::query()->where('discord_user_id', $discordId)->first();
        return $record ? (int) $record->user_id : null;
    }

    /**
     * Unlink a Discord user mapping by its discord user id.
     * Returns true if a mapping existed and was deleted, false otherwise.
     */
    public function unlinkDiscordUserByDiscordId(string $discordId): bool
    {
        return (bool) DiscordUser::query()
            ->where('discord_user_id', $discordId)
            ->delete();
    }
}
