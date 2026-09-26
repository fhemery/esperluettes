<?php

namespace App\Domains\Config\Private\Controllers\Admin;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Exceptions\UndeclaredFeatureToggleException;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FeatureToggleController extends Controller
{
    public function __construct(
        private readonly ConfigPublicApi $api,
        private readonly AuthPublicApi $authApi,
    ) {}

    public function index(): View
    {
        $toggles = $this->api->listFeatureToggles();
        $orphans = $this->api->listOrphanFeatureToggles();

        return view('config::pages.admin.feature-toggles.index', compact('toggles', 'orphans'));
    }

    public function edit(string $domain, string $name): View
    {
        $this->requireTechAdmin();

        $featureToggle = $this->findDeclared($domain, $name);
        $roles = $this->getRoleOptions();

        return view('config::pages.admin.feature-toggles.edit', compact('featureToggle', 'roles'));
    }

    public function update(Request $request, string $domain, string $name): RedirectResponse
    {
        $this->requireTechAdmin();

        $validated = $request->validate([
            'access'  => ['required', 'string', 'in:on,off,role_based'],
            'roles'   => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        try {
            $this->api->updateFeatureToggle(
                $name,
                FeatureToggleAccess::from($validated['access']),
                $domain,
                $validated['roles'] ?? [],
            );
        } catch (UndeclaredFeatureToggleException) {
            abort(404);
        }

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.updated'));
    }

    public function destroy(string $domain, string $name): RedirectResponse
    {
        $this->requireTechAdmin();

        try {
            $this->api->deleteFeatureToggle($name, $domain);
        } catch (DomainException) {
            return redirect()->back()
                ->with('error', __('config::admin.feature_toggles.declared_cannot_be_deleted'));
        }

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.deleted'));
    }

    public function setAccess(Request $request, string $domain, string $name): RedirectResponse
    {
        $validated = $request->validate([
            'access' => ['required', 'string', 'in:on,off,role_based'],
        ]);

        try {
            $this->api->updateFeatureToggle(
                $name,
                FeatureToggleAccess::from($validated['access']),
                $domain,
            );
        } catch (UndeclaredFeatureToggleException) {
            abort(404);
        }

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.access_updated'));
    }

    /**
     * The declared toggle (merged with its row), or 404.
     */
    private function findDeclared(string $domain, string $name): FeatureToggle
    {
        foreach ($this->api->listFeatureToggles() as $toggle) {
            if (strtolower($toggle->domain) === strtolower($domain) && strtolower($toggle->name) === strtolower($name)) {
                return $toggle;
            }
        }

        abort(404);
    }

    private function requireTechAdmin(): void
    {
        if (!auth()->user()?->hasRole(Roles::TECH_ADMIN)) {
            abort(403);
        }
    }

    private function getRoleOptions(): array
    {
        $roles = $this->authApi->getAllRoles();
        $options = [];
        foreach ($roles as $role) {
            /** @var \App\Domains\Auth\Public\Api\Dto\RoleDto $role */
            $options[$role->slug] = $role->name;
        }
        return $options;
    }
}
