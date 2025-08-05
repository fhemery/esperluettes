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
            // Admin domain seeders
            \App\Domains\Admin\Database\Seeders\AdminUserSeeder::class,
        ]);
    }
}
