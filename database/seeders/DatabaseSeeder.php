<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// We will keep this file here to respect Laravel convention when loading seeders
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call domain-specific seeders in order
        $this->call([
            // Auth domain seeders (roles must exist before admin/user seeding)
            \App\Domains\Auth\Database\Seeders\AuthSeeder::class,
            // Admin domain seeders
            \App\Domains\Admin\Database\Seeders\AdminUserSeeder::class,
            // Story domain seeders
            \App\Domains\StoryRef\Database\Seeders\StoryRefSeeder::class,
            // Moderation domain seeders
            \App\Domains\Moderation\Database\Seeders\ModerationSeeder::class,
        ]);
    }
}
