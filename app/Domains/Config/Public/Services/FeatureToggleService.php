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
use Illuminate\Support\Facades\Cache;

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

        Cache::forget($this->allCacheKey());
    }

    public function isToggleEnabled(string $name, ?string $domain = 'config'): bool
    {
        $domain = $domain ?? 'config';
        $all = $this->getAllCached();
        $data = $all['byDomain'][$domain][$name] ?? null;
        if (!$data) {
            return false;
        }

        $access = FeatureToggleAccess::from($data['access']);
        return match ($access) {
            FeatureToggleAccess::ON => true,
            FeatureToggleAccess::OFF => false,
            FeatureToggleAccess::ROLE_BASED => $this->auth->hasAnyRole($data['roles'] ?? []),
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

        Cache::forget($this->allCacheKey());
    }

    public function deleteFeatureToggle(string $name, ?string $domain = 'config'): void
    {
        $domain = $domain ?? 'config';
        $model = $this->repo->findByDomainAndName($domain, $name);
        if (!$model instanceof FeatureToggleModel) {
            // No-op (aligns with current tests)
            return;
        }

        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            throw new AuthorizationException('Only tech admins can delete feature toggles');
        }

        $this->repo->delete($model);

        Cache::forget($this->allCacheKey());
    }

    /**
     * Return all toggles cached as both list and by-domain map.
     * @return array{list: array<int,array{domain:string,name:string,access:string,admin_visibility:string,roles:array}>, byDomain: array<string,array<string,array{domain:string,name:string,access:string,admin_visibility:string,roles:array}>>}
     */
    private function getAllCached(): array
    {
        return Cache::remember($this->allCacheKey(), now()->addMinutes(60), function () {
            $items = $this->repo->all();
            $list = [];
            $byDomain = [];
            foreach ($items as $m) {
                $row = [
                    'domain' => $m->domain,
                    'name' => $m->name,
                    'access' => $m->access,
                    'admin_visibility' => $m->admin_visibility,
                    'roles' => $m->roles ?? [],
                ];
                $list[] = $row;
                $byDomain[$m->domain][$m->name] = $row;
            }
            return ['list' => $list, 'byDomain' => $byDomain];
        });
    }

    private function allCacheKey(): string
    {
        return 'feature_toggles:all';
    }

    /**
     * @return array<int,\App\Domains\Config\Public\Contracts\FeatureToggle>
     */
    public function listFeatureToggles(): array
    {
        // Authorization: tech-admin sees all; admin sees ALL_ADMINS; others see none
        $isTech = $this->auth->hasAnyRole([Roles::TECH_ADMIN]);
        $isAdmin = $isTech || $this->auth->hasAnyRole([Roles::ADMIN]);
        if (!$isAdmin) {
            return [];
        }

        $all = $this->getAllCached();
        $result = [];
        foreach ($all['list'] as $row) {
            $vis = FeatureToggleAdminVisibility::from($row['admin_visibility']);
            if ($isTech || $vis === FeatureToggleAdminVisibility::ALL_ADMINS) {
                $result[] = new \App\Domains\Config\Public\Contracts\FeatureToggle(
                    name: $row['name'],
                    domain: $row['domain'],
                    admin_visibility: $vis,
                    access: FeatureToggleAccess::from($row['access']),
                    roles: $row['roles'] ?? [],
                );
            }
        }
        return $result;
    }
}
