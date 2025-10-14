<?php

namespace App\Domains\Config\Public\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Models\FeatureToggle as FeatureToggleModel;
use App\Domains\Config\Private\Repositories\FeatureToggleRepository;
use App\Domains\Config\Public\Contracts\FeatureToggle as FeatureToggleContract;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class FeatureToggleService
{
    public function __construct(
        private AuthPublicApi $auth,
        private FeatureToggleRepository $repo,
    ) {}

    public function addFeatureToggle(FeatureToggleContract $featureToggle): void
    {
        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            throw new AuthorizationException('Only tech admins can create feature toggles');
        }

        $this->repo->create([
            'domain' => $featureToggle->domain,
            'name' => $featureToggle->name,
            'access' => $featureToggle->access->value,
            'admin_visibility' => $featureToggle->admin_visibility->value,
            'roles' => $featureToggle->roles,
            'updated_by' => Auth::id(),
        ]);
    }

    public function isToggleEnabled(string $name, ?string $domain = 'config'): bool
    {
        $model = $this->repo->findByDomainAndName($domain ?? 'config', $name);
        if (!$model instanceof FeatureToggleModel) {
            return false;
        }

        $access = FeatureToggleAccess::from($model->access);
        return match ($access) {
            FeatureToggleAccess::ON => true,
            FeatureToggleAccess::OFF => false,
            FeatureToggleAccess::ROLE_BASED => $this->auth->hasAnyRole($model->roles ?? []),
        };
    }

    public function updateFeatureToggle(string $name, FeatureToggleAccess $access, ?string $domain = 'config'): void
    {
        $model = $this->repo->findByDomainAndName($domain ?? 'config', $name);
        if (!$model instanceof FeatureToggleModel) {
            // No-op if not found (aligns with current tests)
            return;
        }

        $adminVisibility = FeatureToggleAdminVisibility::from($model->admin_visibility);
        if ($adminVisibility === FeatureToggleAdminVisibility::ALL_ADMINS) {
            if (!$this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new AuthorizationException('Only admins can update this feature toggle');
            }
        } else {
            if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
                throw new AuthorizationException('Only tech admins can update feature toggles');
            }
        }

        $this->repo->update($model, [
            'access' => $access->value,
            'updated_by' => Auth::id(),
        ]);
    }

    public function deleteFeatureToggle(string $name, ?string $domain = 'config'): void
    {
        $model = $this->repo->findByDomainAndName($domain ?? 'config', $name);
        if (!$model instanceof FeatureToggleModel) {
            // No-op (aligns with current tests)
            return;
        }

        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            throw new AuthorizationException('Only tech admins can delete feature toggles');
        }

        $this->repo->delete($model);
    }
}
