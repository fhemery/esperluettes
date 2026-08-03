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
            // Administration domain seeders
            \App\Domains\Administration\Database\Seeders\AdminUserSeeder::class,
            // Story domain seeders
            \App\Domains\StoryRef\Database\Seeders\StoryRefSeeder::class,
            // Moderation domain seeders
            \App\Domains\Moderation\Database\Seeders\ModerationSeeder::class,
        ]);

        // Browser-test fixtures: only ever in the throwaway e2e database.
        // Each domain owns its own, in dependency order.
        if (app()->environment('e2e')) {
            $this->call([
                \App\Domains\Auth\Database\Seeders\E2eAccountsSeeder::class,
                \App\Domains\Profile\Database\Seeders\E2eProfilesSeeder::class,
                \App\Domains\Story\Database\Seeders\E2eStorySeeder::class,
                \App\Domains\Quote\Database\Seeders\E2eQuotesSeeder::class,
                \App\Domains\News\Database\Seeders\E2eNewsSeeder::class,
                \App\Domains\Calendar\Database\Seeders\E2eCalendarSeeder::class,
            ]);
        }
    }
}
