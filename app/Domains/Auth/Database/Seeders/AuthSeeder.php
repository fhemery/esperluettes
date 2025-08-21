<?php

namespace App\Domains\Auth\Database\Seeders;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthSeeder extends Seeder
{
    /**
     * Seed roles and backfill existing users.
     */
    public function run(): void
    {
        // Ensure roles exist (idempotent by slug)
        $roles = [
            ['name' => 'admin', 'slug' => 'admin', 'description' => 'Administrator role'],
            ['name' => 'user', 'slug' => 'user', 'description' => 'Unconfirmed user role'],
            ['name' => 'user-confirmed', 'slug' => 'user-confirmed', 'description' => 'Confirmed user role'],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'description' => $data['description'] ?? null]
            );
        }

        $confirmedRoleId = Role::where('slug', 'user-confirmed')->value('id');
        $userRoleId = Role::where('slug', 'user')->value('id');

        // Backfill existing users
        // - Admins keep admin; also get user-confirmed
        // - If any user has "user" role, switch to "user-confirmed"
        // - Others receive "user-confirmed"
        User::query()
            ->select('users.id')
            ->chunkById(500, function ($users) use ($confirmedRoleId, $userRoleId) {
                $userIds = $users->pluck('id')->all();

                // Fetch roles for these users
                $pivotRows = DB::table('role_user')
                    ->whereIn('user_id', $userIds)
                    ->get(['user_id', 'role_id']);

                $byUser = [];
                foreach ($pivotRows as $row) {
                    $byUser[$row->user_id][] = $row->role_id;
                }

                $now = now();
                $attach = [];
                $detachUserRole = [];

                foreach ($userIds as $uid) {
                    $rolesForUser = $byUser[$uid] ?? [];

                    // Always ensure user-confirmed is present
                    if ($confirmedRoleId && !in_array($confirmedRoleId, $rolesForUser, true)) {
                        $attach[] = ['user_id' => $uid, 'role_id' => $confirmedRoleId, 'created_at' => $now, 'updated_at' => $now];
                    }

                    // If user role present, schedule detach
                    if ($userRoleId && in_array($userRoleId, $rolesForUser, true)) {
                        $detachUserRole[] = $uid;
                    }
                }

                if (!empty($attach)) {
                    DB::table('role_user')->upsert($attach, ['user_id', 'role_id'], ['updated_at']);
                }

                if (!empty($detachUserRole)) {
                    DB::table('role_user')
                        ->whereIn('user_id', $detachUserRole)
                        ->where('role_id', $userRoleId)
                        ->delete();
                }
            });
    }
}
