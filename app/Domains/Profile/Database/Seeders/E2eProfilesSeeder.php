<?php

namespace App\Domains\Profile\Database\Seeders;

use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A profile for every E2E account (see .env.e2e). Mirrored in
 * `e2e/support/fixtures.ts`.
 *
 * Auth creates the accounts but may not reach into Profile, so this seeder
 * picks them up afterwards by their `@e2e.test` address and derives both the
 * slug and the display name from it. That keeps the two domains independent
 * at the cost of one naming convention shared between them:
 *
 *     author@e2e.test  ->  slug 'e2e-author', display name 'E2E Author'
 *
 * The slug is prefixed because the stock admin@example.com already owns
 * 'admin'.
 */
class E2eProfilesSeeder extends Seeder
{
    public const EMAIL_DOMAIN = '@e2e.test';

    public function run(): void
    {
        $users = DB::table('users')
            ->where('email', 'like', '%' . self::EMAIL_DOMAIN)
            ->get(['id', 'email']);

        foreach ($users as $user) {
            $name = explode('@', $user->email)[0];

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => 'E2E ' . ucfirst($name),
                    'slug' => 'e2e-' . str($name)->slug()->value(),
                    'description' => 'Compte de test E2E.',
                ]
            );
        }
    }
}
