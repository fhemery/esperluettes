<?php

namespace App\Domains\Admin\Database\Seeders;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Models\Role;
use App\Domains\Profile\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get the admin role
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['slug' => 'admin', 'description' => 'Administrator role']
        );

        // Create admin user
        $adminUser = User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // You should change this password
            'email_verified_at' => now(),
        ]);

        // Attach the admin role to the user
        $adminUser->roles()->attach($adminRole);

        // Create a profile for the admin user
        Profile::create([
            'user_id' => $adminUser->id,
            'display_name' => 'Admin',
            'description' => 'Admin profile',
            'slug' => 'admin',
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}
