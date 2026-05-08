<?php

namespace App\Domains\Config\Private\Controllers\Admin;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Models\FeatureToggle;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle as FeatureToggleContract;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
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
        $isTech = auth()->user()?->hasRole(Roles::TECH_ADMIN) ?? false;

        $toggles = FeatureToggle::query()
            ->when(!$isTech, fn ($q) => $q->where('admin_visibility', FeatureToggleAdminVisibility::ALL_ADMINS->value))
            ->orderBy('domain')
            ->orderBy('name')
            ->get();

        return view('config::pages.admin.feature-toggles.index', compact('toggles'));
    }

    public function create(): View
    {
        $this->requireTechAdmin();

        $roles = $this->getRoleOptions();

        return view('config::pages.admin.feature-toggles.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireTechAdmin();

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'domain'           => ['required', 'string', 'max:100'],
            'admin_visibility' => ['required', 'string', 'in:tech_admins_only,all_admins'],
            'access'           => ['required', 'string', 'in:on,off,role_based'],
            'roles'            => ['nullable', 'array'],
            'roles.*'          => ['string'],
        ]);

        $feature = new FeatureToggleContract(
            name: $validated['name'],
            domain: $validated['domain'],
            admin_visibility: FeatureToggleAdminVisibility::from($validated['admin_visibility']),
            access: FeatureToggleAccess::from($validated['access']),
            roles: $validated['roles'] ?? [],
        );

        $this->api->addFeatureToggle($feature);

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.created'));
    }

    public function edit(FeatureToggle $featureToggle): View
    {
        $this->requireTechAdmin();

        $roles = $this->getRoleOptions();

        return view('config::pages.admin.feature-toggles.edit', compact('featureToggle', 'roles'));
    }

    public function update(Request $request, FeatureToggle $featureToggle): RedirectResponse
    {
        $this->requireTechAdmin();

        $validated = $request->validate([
            'admin_visibility' => ['required', 'string', 'in:tech_admins_only,all_admins'],
            'access'           => ['required', 'string', 'in:on,off,role_based'],
            'roles'            => ['nullable', 'array'],
            'roles.*'          => ['string'],
        ]);

        $this->api->editFeatureToggle(
            name: $featureToggle->name,
            domain: $featureToggle->domain,
            adminVisibility: FeatureToggleAdminVisibility::from($validated['admin_visibility']),
            access: FeatureToggleAccess::from($validated['access']),
            roles: $validated['roles'] ?? [],
        );

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.updated'));
    }

    public function destroy(FeatureToggle $featureToggle): RedirectResponse
    {
        $this->requireTechAdmin();

        $this->api->deleteFeatureToggle($featureToggle->name, $featureToggle->domain);

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.deleted'));
    }

    public function setAccess(Request $request, FeatureToggle $featureToggle): RedirectResponse
    {
        $validated = $request->validate([
            'access' => ['required', 'string', 'in:on,off,role_based'],
        ]);

        $this->api->updateFeatureToggle(
            $featureToggle->name,
            FeatureToggleAccess::from($validated['access']),
            $featureToggle->domain,
        );

        return redirect()->route('config.admin.feature-toggles.index')
            ->with('success', __('config::admin.feature_toggles.access_updated'));
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
