<?php

namespace App\Domains\Auth\Private\Controllers\Admin;

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Private\Requests\Admin\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('auth::pages.admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('auth::pages.admin.roles.create');
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        Role::create($request->validated());

        return redirect()
            ->route('auth.admin.roles.index')
            ->with('success', __('auth::admin.roles.created'));
    }

    public function edit(Role $role): View
    {
        return view('auth::pages.admin.roles.edit', compact('role'));
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        return redirect()
            ->route('auth.admin.roles.index')
            ->with('success', __('auth::admin.roles.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', __('auth::admin.roles.delete_blocked'));
        }

        $role->delete();

        return redirect()
            ->route('auth.admin.roles.index')
            ->with('success', __('auth::admin.roles.deleted'));
    }
}
