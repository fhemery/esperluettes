<?php

namespace Database\Seeders;

use App\Domains\Auth\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call domain-specific seeders
        $this->call([
            \App\Domains\Admin\Database\Seeders\AdminUserSeeder::class,
        ]);
        
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
