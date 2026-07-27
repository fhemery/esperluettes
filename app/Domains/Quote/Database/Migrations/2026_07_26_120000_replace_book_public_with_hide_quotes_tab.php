<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The quote book used to be hidden by default and opted into via `book_public`.
 * It is now visible by default and opted out of via `hide-quotes-tab`, matching
 * the other profile-tab privacy settings (follow, comments).
 *
 * Only an explicit `book_public = 0` was a deliberate "keep it hidden" choice,
 * so it is the only value carried over. Rows storing `1` are dropped: the tab is
 * visible by default now, which is what those users wanted anyway.
 */
return new class extends Migration
{
    private const DOMAIN = 'profile';
    private const OLD_KEY = 'book_public';
    private const NEW_KEY = 'hide-quotes-tab';

    public function up(): void
    {
        $rows = DB::table('settings')
            ->where('domain', self::DOMAIN)
            ->where('key', self::OLD_KEY)
            ->get(['user_id', 'value']);

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();
        $hidden = $rows
            ->filter(fn ($row) => !filter_var($row->value, FILTER_VALIDATE_BOOLEAN))
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'domain' => self::DOMAIN,
                'key' => self::NEW_KEY,
                'value' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($hidden !== []) {
            DB::table('settings')->insertOrIgnore($hidden);
        }

        DB::table('settings')
            ->where('domain', self::DOMAIN)
            ->where('key', self::OLD_KEY)
            ->delete();

        foreach ($rows->pluck('user_id')->unique() as $userId) {
            Cache::forget("user_settings:{$userId}");
        }
    }

    public function down(): void
    {
        $rows = DB::table('settings')
            ->where('domain', self::DOMAIN)
            ->where('key', self::NEW_KEY)
            ->get(['user_id', 'value']);

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();
        // Restoring the old semantics: hidden => book_public = 0.
        $restored = $rows
            ->filter(fn ($row) => filter_var($row->value, FILTER_VALIDATE_BOOLEAN))
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'domain' => self::DOMAIN,
                'key' => self::OLD_KEY,
                'value' => '0',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($restored !== []) {
            DB::table('settings')->insertOrIgnore($restored);
        }

        DB::table('settings')
            ->where('domain', self::DOMAIN)
            ->where('key', self::NEW_KEY)
            ->delete();

        foreach ($rows->pluck('user_id')->unique() as $userId) {
            Cache::forget("user_settings:{$userId}");
        }
    }
};
