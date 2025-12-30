<?php

namespace App\Domains\Settings\Private\Repositories;

use App\Domains\Settings\Private\Models\Setting;
use Illuminate\Support\Collection;

class SettingRepository
{
    /**
     * Get all stored settings for a user.
     *
     * @return Collection<int, Setting>
     */
    public function getAllForUser(int $userId): Collection
    {
        return Setting::where('user_id', $userId)->get();
    }

    /**
     * Find a setting by user, domain and key.
     */
    public function findByUserDomainAndKey(int $userId, string $domain, string $key): ?Setting
    {
        return Setting::where('user_id', $userId)
            ->where('domain', $domain)
            ->where('key', $key)
            ->first();
    }

    /**
     * Create or update a setting value.
     *
     * @param array{user_id: int, domain: string, key: string, value: string} $data
     */
    public function upsert(array $data): Setting
    {
        return Setting::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'domain' => $data['domain'],
                'key' => $data['key'],
            ],
            ['value' => $data['value']]
        );
    }

    /**
     * Delete a setting (reset to default).
     */
    public function delete(Setting $model): void
    {
        $model->delete();
    }

    /**
     * Delete a setting by user, domain and key.
     */
    public function deleteByUserDomainAndKey(int $userId, string $domain, string $key): bool
    {
        return Setting::where('user_id', $userId)
            ->where('domain', $domain)
            ->where('key', $key)
            ->delete() > 0;
    }

    /**
     * Delete all settings for a user.
     */
    public function deleteAllForUser(int $userId): int
    {
        return Setting::where('user_id', $userId)->delete();
    }
}
