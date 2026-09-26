<?php

namespace App\Domains\Config\Public\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Models\FeatureToggle as FeatureToggleModel;
use App\Domains\Config\Private\Repositories\FeatureToggleRepository;
use App\Domains\Config\Private\Support\ConfigStorageReadiness;
use App\Domains\Config\Public\Contracts\FeatureToggle as FeatureToggleContract;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Contracts\FeatureToggleDefinition;
use App\Domains\Config\Public\Exceptions\UndeclaredFeatureToggleException;
use App\Domains\Config\Public\Events\DTO\FeatureToggleSnapshot;
use App\Domains\Config\Public\Events\FeatureToggleAdded;
use App\Domains\Config\Public\Events\FeatureToggleDeleted;
use App\Domains\Config\Public\Events\FeatureToggleUpdated;
use App\Domains\Events\Public\Api\EventBus;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FeatureToggleService
{
    /**
     * In-memory registry of toggle declarations, keyed by lowercased domain then name.
     * Populated during ServiceProvider boot().
     *
     * @var array<string, array<string, FeatureToggleDefinition>>
     */
    private static array $definitions = [];

    public function __construct(
        private AuthPublicApi $auth,
        private FeatureToggleRepository $repo,
        private EventBus $events,
    ) {}

    public function addFeatureToggle(FeatureToggleContract $featureToggle): void
    {
        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            throw new AuthorizationException('Only tech admins can create feature toggles');
        }

        // Validate inputs and uniqueness
        $this->assertNewToggleIsValid($featureToggle);

        $this->repo->create([
            'domain' => $featureToggle->domain,
            'name' => $featureToggle->name,
            'access' => $featureToggle->access->value,
            'admin_visibility' => $featureToggle->admin_visibility->value,
            'roles' => $featureToggle->roles,
            'updated_by' => Auth::id(),
        ]);

        Cache::forget($this->allCacheKey());

        // Emit domain event
        $snapshot = FeatureToggleSnapshot::fromFeatureToggle($featureToggle);
        $this->events->emit(new FeatureToggleAdded($snapshot));
    }

    /**
     * Declare a feature toggle. Called from domain ServiceProviders during boot().
     * Declaring the same toggle twice keeps the last declaration.
     */
    public function registerFeatureToggle(FeatureToggleDefinition $definition): void
    {
        self::$definitions[strtolower($definition->domain)][strtolower($definition->name)] = $definition;
    }

    public function getDefinition(string $name, ?string $domain = 'config'): ?FeatureToggleDefinition
    {
        return self::$definitions[strtolower($domain ?? 'config')][strtolower($name)] ?? null;
    }

    /**
     * Clear all declarations (for testing).
     */
    public static function clearDefinitions(): void
    {
        self::$definitions = [];
    }

    public function isToggleEnabled(string $name, ?string $domain = 'config'): bool
    {
        $domain = strtolower($domain ?? 'config');
        $name = strtolower($name);
        if ($this->getDefinition($name, $domain) === null) {
            throw new UndeclaredFeatureToggleException($domain, $name);
        }

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

    /**
     * Set the state of a declared toggle, creating its row on the first change.
     * $roles === null keeps the current roles ([] for a new row).
     */
    public function updateFeatureToggle(string $name, FeatureToggleAccess $access, ?string $domain = 'config', ?array $roles = null): void
    {
        $domainKey = strtolower($domain ?? 'config');
        $nameKey = strtolower($name);
        $definition = $this->getDefinition($nameKey, $domainKey);
        if ($definition === null) {
            throw new UndeclaredFeatureToggleException($domainKey, $nameKey);
        }

        if ($definition->adminVisibility === FeatureToggleAdminVisibility::ALL_ADMINS) {
            if (!$this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new AuthorizationException('Only admins can update this feature toggle');
            }
        } else {
            if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
                throw new AuthorizationException('Only tech admins can update feature toggles');
            }
        }

        // Resolve an existing row via cache (case-insensitive), then load the exact model
        $row = $this->getAllCached()['byDomain'][$domainKey][$nameKey] ?? null;
        $model = $row ? $this->repo->findByDomainAndName($row['domain'], $row['name']) : null;

        $data = [
            'access' => $access->value,
            'admin_visibility' => $definition->adminVisibility->value,
            'updated_by' => Auth::id(),
        ];
        if ($roles !== null) {
            $data['roles'] = $roles;
        }

        if ($model instanceof FeatureToggleModel) {
            $this->repo->update($model, $data);
        } else {
            $model = $this->repo->create($data + [
                'domain' => $domainKey,
                'name' => $nameKey,
                'roles' => [],
            ]);
        }

        Cache::forget($this->allCacheKey());

        $toggle = new FeatureToggleContract(
            name: $model->name,
            domain: $model->domain,
            admin_visibility: $definition->adminVisibility,
            access: $access,
            roles: $model->roles ?? [],
        );
        $snapshot = FeatureToggleSnapshot::fromFeatureToggle($toggle);
        $this->events->emit(new FeatureToggleUpdated($snapshot));
    }

    public function editFeatureToggle(string $name, string $domain, FeatureToggleAdminVisibility $adminVisibility, FeatureToggleAccess $access, array $roles): void
    {
        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            throw new AuthorizationException('Only tech admins can edit feature toggles');
        }

        $all = $this->getAllCached();
        $row = $all['byDomain'][strtolower($domain)][strtolower($name)] ?? null;
        if (!$row) {
            return;
        }

        $model = $this->repo->findByDomainAndName($row['domain'], $row['name']);
        if (!$model instanceof FeatureToggleModel) {
            return;
        }

        $this->repo->update($model, [
            'admin_visibility' => $adminVisibility->value,
            'access' => $access->value,
            'roles' => $roles,
            'updated_by' => Auth::id(),
        ]);

        Cache::forget($this->allCacheKey());

        $toggle = new \App\Domains\Config\Public\Contracts\FeatureToggle(
            name: $model->name,
            domain: $model->domain,
            admin_visibility: $adminVisibility,
            access: $access,
            roles: $roles,
        );
        $snapshot = FeatureToggleSnapshot::fromFeatureToggle($toggle);
        $this->events->emit(new FeatureToggleUpdated($snapshot));
    }

    /**
     * Delete an orphan row (a row no declaration matches). Declared toggles cannot be deleted.
     */
    public function deleteFeatureToggle(string $name, ?string $domain = 'config'): void
    {
        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            throw new AuthorizationException('Only tech admins can delete feature toggles');
        }

        $domainKey = strtolower($domain ?? 'config');
        $nameKey = strtolower($name);
        if ($this->getDefinition($nameKey, $domainKey) !== null) {
            throw new DomainException("Feature toggle {$domainKey}/{$nameKey} is declared and cannot be deleted.");
        }

        // Resolve original row via cache for case-insensitive behavior
        $row = $this->getAllCached()['byDomain'][$domainKey][$nameKey] ?? null;
        if (!$row) {
            return;
        }

        // Find and delete the exact model
        $model = $this->repo->findByDomainAndName($row['domain'], $row['name']);
        if ($model instanceof FeatureToggleModel) {
            $this->repo->delete($model);
        }

        // Invalidate cache first so subsequent reads miss
        Cache::forget($this->allCacheKey());

        // Emit domain event with snapshot from the resolved row (orphans are tech-admin-only)
        $snapshot = FeatureToggleSnapshot::fromFeatureToggle($this->orphanFromRow($row));
        $this->events->emit(new FeatureToggleDeleted($snapshot));
    }

    /**
     * @param array{domain:string,name:string,access:string,roles:array} $row
     */
    private function orphanFromRow(array $row): FeatureToggleContract
    {
        return new FeatureToggleContract(
            name: $row['name'],
            domain: $row['domain'],
            admin_visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
            access: FeatureToggleAccess::from($row['access']),
            roles: $row['roles'] ?? [],
        );
    }

    /**
     * Return all toggles cached as both list and by-domain map.
     * @return array{list: array<int,array{domain:string,name:string,access:string,admin_visibility:string,roles:array}>, byDomain: array<string,array<string,array{domain:string,name:string,access:string,admin_visibility:string,roles:array}>>}
     */
    private function getAllCached(): array
    {
        if (! ConfigStorageReadiness::isAvailable()) {
            return ['list' => [], 'byDomain' => []];
        }

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
                $byDomain[strtolower($m->domain)][strtolower($m->name)] = $row;
            }
            return ['list' => $list, 'byDomain' => $byDomain];
        });
    }

    private function allCacheKey(): string
    {
        return 'feature_toggles:all';
    }

    /**
     * Validate required fields and uniqueness for a new feature toggle.
     * Throws ValidationException on error.
     */
    private function assertNewToggleIsValid(FeatureToggleContract $featureToggle): void
    {
        // Basic required validation (trim to guard whitespace-only)
        $data = [
            'name' => trim((string) $featureToggle->name),
            'domain' => trim((string) $featureToggle->domain),
        ];
        $rules = [
            'name' => ['required', 'string', 'min:1'],
            'domain' => ['required', 'string', 'min:1'],
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Uniqueness (case-insensitive on domain+name)
        $existing = $this->repo->all();
        $dn = strtolower($data['domain']);
        $nn = strtolower($data['name']);
        foreach ($existing as $m) {
            if (strtolower($m->domain) === $dn && strtolower($m->name) === $nn) {
                throw ValidationException::withMessages([
                    'name' => [__('This feature toggle already exists in this domain.')],
                ]);
            }
        }
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

        // One entry per declaration, merged with its row; visibility always from the declaration
        $byDomain = $this->getAllCached()['byDomain'];
        $definitions = self::$definitions;
        ksort($definitions);
        $result = [];
        foreach ($definitions as $domainKey => $names) {
            ksort($names);
            foreach ($names as $nameKey => $definition) {
                if (!$isTech && $definition->adminVisibility !== FeatureToggleAdminVisibility::ALL_ADMINS) {
                    continue;
                }
                $row = $byDomain[$domainKey][$nameKey] ?? null;
                $result[] = new FeatureToggleContract(
                    name: $definition->name,
                    domain: $definition->domain,
                    admin_visibility: $definition->adminVisibility,
                    access: $row ? FeatureToggleAccess::from($row['access']) : FeatureToggleAccess::OFF,
                    roles: $row['roles'] ?? [],
                );
            }
        }
        return $result;
    }

    /**
     * Rows that no declaration matches. Tech admins only.
     *
     * @return array<int,FeatureToggleContract>
     */
    public function listOrphanFeatureToggles(): array
    {
        if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
            return [];
        }

        $result = [];
        foreach ($this->getAllCached()['list'] as $row) {
            if ($this->getDefinition($row['name'], $row['domain']) === null) {
                $result[] = $this->orphanFromRow($row);
            }
        }
        return $result;
    }
}
