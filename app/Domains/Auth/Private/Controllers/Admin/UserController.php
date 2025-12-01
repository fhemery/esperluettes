<?php

declare(strict_types=1);

namespace App\Domains\Auth\Private\Controllers\Admin;

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Private\Services\AdminUserService;
use App\Domains\Auth\Private\Services\ComplianceService;
use App\Domains\Auth\Private\Services\RoleService;
use App\Domains\Auth\Private\Services\UserService;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
        private readonly ComplianceService $complianceService,
        private readonly RoleService $roleService,
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->input('search'),
            'is_active' => $request->input('is_active'),
        ];

        $users = $this->adminUserService->getPaginatedUsers($filters);
        $roles = $this->adminUserService->getAllRoles();

        return view('auth::pages.admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $filters,
        ]);
    }

    public function edit(User $user): View
    {
        $displayName = $this->adminUserService->getUserDisplayName($user->id);
        $roles = $this->adminUserService->getAllRoles();

        return view('auth::pages.admin.users.edit', [
            'user' => $user,
            'displayName' => $displayName,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user->update(['email' => $validated['email']]);

        // Update roles
        $selectedRoleIds = collect($validated['roles'] ?? [])->map(fn ($v) => (int) $v)->filter()->values();
        $selectedSlugs = Role::query()->whereIn('id', $selectedRoleIds)->pluck('slug')->all();
        $currentSlugs = $user->roles()->pluck('slug')->all();

        $toGrant = array_values(array_diff($selectedSlugs, $currentSlugs));
        $toRevoke = array_values(array_diff($currentSlugs, $selectedSlugs));

        foreach ($toGrant as $slug) {
            $this->roleService->grant($user, $slug);
        }
        foreach ($toRevoke as $slug) {
            $this->roleService->revoke($user, $slug);
        }

        return redirect()
            ->route('auth.admin.users.index')
            ->with('success', __('auth::admin.users.updated'));
    }

    public function promote(User $user): RedirectResponse
    {
        if ($user->hasRole(Roles::USER)) {
            $this->roleService->revoke($user, Roles::USER);
        }
        if (!$user->hasRole(Roles::USER_CONFIRMED)) {
            $this->roleService->grant($user, Roles::USER_CONFIRMED);
        }

        $roleName = Role::where('slug', Roles::USER_CONFIRMED)->value('name') ?? Roles::USER_CONFIRMED;

        return redirect()
            ->back()
            ->with('success', __('admin::auth.users.promote.success', ['role' => $roleName]));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->deleteUser($user);

        return redirect()
            ->route('auth.admin.users.index')
            ->with('success', __('admin::auth.users.deletion.success'));
    }

    public function downloadAuthorization(User $user): StreamedResponse|RedirectResponse
    {
        $response = $this->complianceService->downloadParentalAuthorization($user->id);

        if ($response === null) {
            return redirect()
                ->back()
                ->with('error', __('auth::admin.users.authorization.not_found'));
        }

        return $response;
    }

    public function clearAuthorization(User $user): RedirectResponse|Response
    {
        if (!$user->is_under_15 || !$user->parental_authorization_verified_at) {
            return redirect()
                ->back()
                ->with('error', __('auth::admin.users.authorization.cannot_clear'));
        }

        $this->complianceService->clearParentalAuthorization($user);

        session()->flash('success', __('auth::admin.users.authorization.cleared'));
        return response()->noContent();
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'email' => __('admin::auth.users.email_header'),
            'is_active' => __('admin::auth.users.is_active_header'),
            'is_under_15' => __('auth::admin.users.table.is_minor'),
            'parental_authorization_verified_at' => __('auth::admin.users.table.authorization_verified'),
            'email_verified_at' => __('admin::auth.users.email_verified_at_header'),
            'terms_accepted_at' => __('auth::admin.users.table.terms_accepted'),
            'created_at' => __('admin::shared.column.created_at'),
            'updated_at' => __('admin::shared.column.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            User::query(),
            $columns,
            'users.csv'
        );
    }

    public function deactivate(User $user): Response
    {
        $this->userService->deactivateUser($user);

        return response()->noContent();
    }

    public function reactivate(User $user): Response
    {
        $this->userService->activateUser($user);

        return response()->noContent();
    }
}
