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
        // Fetch the admin role (created by AuthSeeder)
        $adminRole = Role::where('slug', 'admin')->first();
        if (!$adminRole) {
            $this->command?->warn('Admin role not found. Ensure AuthSeeder runs before AdminUserSeeder.');
            return;
        }

        // If there is already at least one user with the admin role, do nothing
        $hasAnyAdmin = $adminRole->users()->exists();
        if ($hasAnyAdmin) {
            $this->command?->info('An admin user already exists. Skipping admin user creation.');
            return;
        }

        // Create or get admin user (idempotent by email)
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'password' => Hash::make('password'), // You should change this password
                'email_verified_at' => now(),
            ]
        );

        // Attach the admin role to the user if not already attached
        if (!$adminUser->roles()->where('roles.id', $adminRole->id)->exists()) {
            $adminUser->roles()->attach($adminRole);
        }

        // Create a profile for the admin user
        Profile::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'display_name' => 'Admin',
                'description' => 'Admin profile',
                'slug' => 'admin',
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}
