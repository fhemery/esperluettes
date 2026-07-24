<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userIds = DB::table('settings')
            ->where('domain', 'quote')
            ->where('key', 'book_public')
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('settings')
            ->where('domain', 'quote')
            ->where('key', 'book_public')
            ->update(['domain' => 'profile']);

        foreach ($userIds as $userId) {
            Cache::forget("user_settings:{$userId}");
        }
    }

    public function down(): void
    {
        $userIds = DB::table('settings')
            ->where('domain', 'profile')
            ->where('key', 'book_public')
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('settings')
            ->where('domain', 'profile')
            ->where('key', 'book_public')
            ->update(['domain' => 'quote']);

        foreach ($userIds as $userId) {
            Cache::forget("user_settings:{$userId}");
        }
    }
};
