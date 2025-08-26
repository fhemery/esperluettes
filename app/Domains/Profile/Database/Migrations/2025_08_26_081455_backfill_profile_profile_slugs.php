<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('profile_profiles')) {
            return;
        }
        if (!Schema::hasColumn('profile_profiles', 'slug')) {
            return;
        }

        $hasDisplay = Schema::hasColumn('profile_profiles', 'display_name');

        DB::table('profile_profiles')
            ->whereNull('slug')
            ->orderBy('user_id')
            ->chunkById(500, function ($rows) use ($hasDisplay) {
                foreach ($rows as $row) {
                    $userId = (int) $row->user_id;

                    $base = '';
                    if ($hasDisplay) {
                        $name = is_string($row->display_name ?? null) ? trim($row->display_name) : '';
                        if ($name !== '') {
                            $base = Str::slug($name);
                        }
                    }
                    if ($base === '') {
                        $base = 'user-' . $userId;
                    }

                    $slug = $base;
                    $i = 0;
                    while (
                        DB::table('profile_profiles')
                            ->where('slug', $slug)
                            ->where('user_id', '!=', $userId)
                            ->exists()
                    ) {
                        $i++;
                        $slug = $base . '-' . $i;
                    }

                    DB::table('profile_profiles')
                        ->where('user_id', $userId)
                        ->update(['slug' => $slug]);
                }
            }, 'user_id');
    }

    public function down(): void
    {
        // No-op: backfill cannot be safely reversed without losing data intent.
    }
};
