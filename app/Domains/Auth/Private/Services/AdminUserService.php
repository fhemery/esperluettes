<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(
        private readonly ProfilePublicApi $profileApi,
    ) {}

    /**
     * Get paginated list of users for admin with profile display names.
     *
     * @param array{search?: string|null, is_active?: string|null} $filters
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        // Search filter using ProfilePublicApi
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            // First search profiles to get matching user IDs
            $matchingUserIds = $this->profileApi->searchDisplayNames($search, 1000, true);

            $query->where(function ($q) use ($search, $matchingUserIds) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('id', $search);

                if (!empty($matchingUserIds)) {
                    $q->orWhereIn('id', array_keys($matchingUserIds));
                }
            });
        }

        // Active filter
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $query->orderBy('created_at', 'desc');

        $users = $query->paginate($perPage);

        // Enrich users with display names from profile
        $userIds = $users->pluck('id')->toArray();
        $profiles = $this->profileApi->getPublicProfiles($userIds);

        // Add display_name to each user
        $users->getCollection()->transform(function ($user) use ($profiles) {
            $user->profile_display_name = $profiles[$user->id]?->display_name ?? null;
            return $user;
        });

        return $users;
    }

    /**
     * Get display name for a single user.
     */
    public function getUserDisplayName(int $userId): ?string
    {
        $profile = $this->profileApi->getPublicProfile($userId);
        return $profile?->display_name;
    }

    /**
     * Get all roles for assignment.
     */
    public function getAllRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::all();
    }

    /**
     * Update user email and roles.
     */
    public function updateUser(User $user, string $email, array $roleIds): void
    {
        $user->update(['email' => $email]);
        $user->roles()->sync($roleIds);
    }

    /**
     * Promote user from 'user' to 'user-confirmed'.
     */
    public function promoteUser(User $user): bool
    {
        if (!$user->hasRole(Roles::USER)) {
            return false;
        }

        $user->removeRole(Roles::USER);
        $user->assignRole(Roles::USER_CONFIRMED);

        return true;
    }

    /**
     * Export users to CSV format.
     *
     * @return array{headers: array, rows: \Generator}
     */
    public function exportToCsv(): array
    {
        $headers = ['ID', 'Email', 'Display Name', 'Active', 'Email Verified', 'Roles', 'Created At'];

        $users = User::with('roles')->orderBy('id')->cursor();

        // Get all user IDs first for batch profile lookup
        $allUserIds = User::pluck('id')->toArray();
        $profiles = $this->profileApi->getPublicProfiles($allUserIds);

        $rows = (function () use ($users, $profiles) {
            foreach ($users as $user) {
                yield [
                    $user->id,
                    $user->email,
                    $profiles[$user->id]?->display_name ?? '',
                    $user->is_active ? 'Yes' : 'No',
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->roles->pluck('name')->implode(', '),
                    $user->created_at->format('Y-m-d H:i:s'),
                ];
            }
        })();

        return ['headers' => $headers, 'rows' => $rows];
    }
}
