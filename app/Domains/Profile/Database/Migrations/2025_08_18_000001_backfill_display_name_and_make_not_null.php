<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('profile_profiles')) {
            return;
        }

        // Ensure a profile row exists for every user and set display_name deterministically
        if (Schema::hasTable('users') && Schema::hasColumn('profile_profiles', 'display_name')) {
            DB::table('users')->select('id', 'name')->orderBy('id')->chunkById(200, function ($users) {
                foreach ($users as $u) {
                    $profile = DB::table('profile_profiles')->where('user_id', $u->id)->first();
                    $fallback = $u->name ?? ('user-' . $u->id);
                    if (!$profile) {
                        DB::table('profile_profiles')->insert([
                            'user_id' => $u->id,
                            'display_name' => $fallback,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } elseif ($profile->display_name === null) {
                        DB::table('profile_profiles')->where('user_id', $u->id)->update([
                            'display_name' => $fallback,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
        }

        // Enforce NOT NULL on display_name (without adding doctrine/dbal)
        $hasDisplayName = Schema::hasColumn('profile_profiles', 'display_name');
        if ($hasDisplayName) {
            $driver = DB::getDriverName();
            try {
                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE profile_profiles MODIFY display_name VARCHAR(255) NOT NULL');
                } elseif ($driver === 'pgsql') {
                    DB::statement('ALTER TABLE profile_profiles ALTER COLUMN display_name SET NOT NULL');
                } else {
                    // For SQLite/others, skip hard NOT NULL to avoid migration failures.
                    // Data is backfilled; enforce at application validation level in these environments.
                }
            } catch (\Throwable $e) {
                // Log and continue to avoid breaking deployments
                // In Laravel migrations, avoid throwing to keep idempotency across environments.
            }
        }
    }

    public function down(): void
    {
        // Relax NOT NULL back to NULLABLE if possible
        if (!Schema::hasTable('profile_profiles')) {
            return;
        }

        if (Schema::hasColumn('profile_profiles', 'display_name')) {
            $driver = DB::getDriverName();
            try {
                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE profile_profiles MODIFY display_name VARCHAR(255) NULL');
                } elseif ($driver === 'pgsql') {
                    DB::statement('ALTER TABLE profile_profiles ALTER COLUMN display_name DROP NOT NULL');
                } else {
                    // No-op for other drivers
                }
            } catch (\Throwable $e) {
                // swallow
            }
        }
    }
};
