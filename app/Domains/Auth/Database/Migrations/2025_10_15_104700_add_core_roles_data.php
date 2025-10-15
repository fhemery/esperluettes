<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert required roles if missing (idempotent by DB constraint on unique slug)
        $now = now();
        $roles = [
            ['name' => 'admin', 'slug' => 'admin', 'description' => 'Administrator role'],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Unconfirmed user role'],
            ['name' => 'User Confirmed', 'slug' => 'user-confirmed', 'description' => 'Confirmed user role'],
            ['name' => 'Tech Admin', 'slug' => 'tech-admin', 'description' => 'Technical administrator role'],
            ['name' => 'Moderator', 'slug' => 'moderator', 'description' => 'Responsible for moderation'],
        ];

        foreach ($roles as $data) {
            DB::table('roles')->insertOrIgnore([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Remove only the roles inserted by this migration
        DB::table('roles')->whereIn('slug', [
            'admin',
            'user',
            'user-confirmed',
            'tech-admin',
            'moderator',
        ])->delete();
    }
};
