<?php

namespace App\Domains\Auth\Database\Seeders;

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One account per role for the E2E environment (see .env.e2e).
 *
 * Runs only against the throwaway e2e database, which is rebuilt before every
 * `npm run e2e`, so these addresses are constants the specs can rely on —
 * mirrored in `e2e/support/fixtures.ts`, which must be kept in step.
 *
 * Profiles are Profile's business: E2eProfilesSeeder picks these accounts up
 * afterwards by their address.
 *
 * Terms are deliberately *not* pre-accepted, so every run exercises the CGU
 * gate at least once — it is a real part of logging in.
 */
class E2eAccountsSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** email => roles */
    public const ACCOUNTS = [
        'admin@e2e.test' => [Roles::ADMIN, Roles::USER_CONFIRMED],
        'moderator@e2e.test' => [Roles::MODERATOR, Roles::USER_CONFIRMED],
        'author@e2e.test' => [Roles::USER_CONFIRMED],
        'confirmed@e2e.test' => [Roles::USER_CONFIRMED],
        'user@e2e.test' => [Roles::USER],
    ];

    public const AUTHOR_EMAIL = 'author@e2e.test';
    public const ADMIN_EMAIL = 'admin@e2e.test';
    public const CONFIRMED_EMAIL = 'confirmed@e2e.test';

    public function run(): void
    {
        foreach (self::ACCOUNTS as $email => $roleSlugs) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ]
            );

            $user->roles()->syncWithoutDetaching(Role::whereIn('slug', $roleSlugs)->pluck('id')->all());
        }
    }
}
