<?php

namespace App\Domains\Config\Public\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Repositories\ConfigParameterRepository;
use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Config\Public\Contracts\ConfigParameterVisibility;
use App\Domains\Config\Public\Events\ConfigParameterUpdated;
use App\Domains\Config\Public\Events\DTO\ConfigParameterSnapshot;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ConfigParameterService
{
    /**
     * In-memory registry of parameter definitions.
     * Populated during ServiceProvider boot().
     *
     * @var array<string, array<string, ConfigParameterDefinition>>
     */
    private static array $definitions = [];

    public function __construct(
        private AuthPublicApi $auth,
        private ConfigParameterRepository $repo,
        private EventBus $events,
    ) {}

    /**
     * Register a parameter definition.
     * Called from domain ServiceProviders during boot().
     */
    public function registerParameter(ConfigParameterDefinition $definition): void
    {
        $domain = strtolower($definition->domain);
        $key = strtolower($definition->key);

        self::$definitions[$domain][$key] = $definition;
    }

    /**
     * Get current value for a parameter.
     * Returns default if no override exists.
     * Returns null if parameter not registered.
     */
    public function getParameterValue(string $key, string $domain): mixed
    {
        $definition = $this->getDefinition($key, $domain);
        if (!$definition) {
            return null;
        }

        $cached = $this->getAllCached();
        $domainKey = strtolower($domain);
        $keyKey = strtolower($key);

        if (isset($cached['byDomain'][$domainKey][$keyKey])) {
            return $definition->cast($cached['byDomain'][$domainKey][$keyKey]);
        }

        return $definition->default;
    }

    /**
     * Update parameter value.
     * Validates against constraints.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws AuthorizationException
     */
    public function setParameterValue(string $key, string $domain, mixed $value): void
    {
        $definition = $this->getDefinition($key, $domain);
        if (!$definition) {
            return;
        }

        $this->assertCanAccess($definition);
        $definition->validate($value);

        $previousValue = $this->getParameterValue($key, $domain);

        $this->repo->upsert([
            'domain' => $definition->domain,
            'key' => $definition->key,
            'value' => $definition->serialize($value),
            'updated_by' => Auth::id(),
        ]);

        Cache::forget($this->allCacheKey());

        $snapshot = ConfigParameterSnapshot::fromDefinitionAndValues($definition, $value, $previousValue);
        $this->events->emit(new ConfigParameterUpdated($snapshot));
    }

    /**
     * Reset parameter to default (removes override from DB).
     *
     * @throws AuthorizationException
     */
    public function resetParameterToDefault(string $key, string $domain): void
    {
        $definition = $this->getDefinition($key, $domain);
        if (!$definition) {
            return;
        }

        $this->assertCanAccess($definition);

        $previousValue = $this->getParameterValue($key, $domain);

        $this->repo->deleteByDomainAndKey($definition->domain, $definition->key);

        Cache::forget($this->allCacheKey());

        $snapshot = ConfigParameterSnapshot::fromDefinitionAndValues($definition, $definition->default, $previousValue);
        $this->events->emit(new ConfigParameterUpdated($snapshot));
    }

    /**
     * List all parameter definitions visible to current admin.
     *
     * @return array<ConfigParameterDefinition>
     */
    public function listParameters(): array
    {
        $isTech = $this->auth->hasAnyRole([Roles::TECH_ADMIN]);
        $isAdmin = $isTech || $this->auth->hasAnyRole([Roles::ADMIN]);

        if (!$isAdmin) {
            return [];
        }

        $result = [];
        foreach (self::$definitions as $domainDefs) {
            foreach ($domainDefs as $definition) {
                if ($isTech || $definition->visibility === ConfigParameterVisibility::ALL_ADMINS) {
                    $result[] = $definition;
                }
            }
        }

        return $result;
    }

    /**
     * Get all current values (definitions + overrides merged).
     * Used by admin page.
     *
     * @return array<array{definition: ConfigParameterDefinition, value: mixed, isOverridden: bool}>
     */
    public function listParametersWithValues(): array
    {
        $definitions = $this->listParameters();
        $cached = $this->getAllCached();

        $result = [];
        foreach ($definitions as $definition) {
            $domainKey = strtolower($definition->domain);
            $keyKey = strtolower($definition->key);
            $isOverridden = isset($cached['byDomain'][$domainKey][$keyKey]);

            $value = $isOverridden
                ? $definition->cast($cached['byDomain'][$domainKey][$keyKey])
                : $definition->default;

            $result[] = [
                'definition' => $definition,
                'value' => $value,
                'isOverridden' => $isOverridden,
            ];
        }

        return $result;
    }

    /**
     * Get a parameter definition by key and domain.
     */
    public function getDefinition(string $key, string $domain): ?ConfigParameterDefinition
    {
        $domainKey = strtolower($domain);
        $keyKey = strtolower($key);

        return self::$definitions[$domainKey][$keyKey] ?? null;
    }

    /**
     * Check if current user can access a parameter based on its visibility.
     *
     * @throws AuthorizationException
     */
    private function assertCanAccess(ConfigParameterDefinition $definition): void
    {
        if ($definition->visibility === ConfigParameterVisibility::ALL_ADMINS) {
            if (!$this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new AuthorizationException('Only admins can update this parameter');
            }
        } else {
            if (!$this->auth->hasAnyRole([Roles::TECH_ADMIN])) {
                throw new AuthorizationException('Only tech admins can update this parameter');
            }
        }
    }

    /**
     * Get all cached parameter values.
     *
     * @return array{byDomain: array<string, array<string, string>>}
     */
    private function getAllCached(): array
    {
        return Cache::remember($this->allCacheKey(), now()->addMinutes(60), function () {
            $items = $this->repo->all();
            $byDomain = [];

            foreach ($items as $item) {
                $byDomain[strtolower($item->domain)][strtolower($item->key)] = $item->value;
            }

            return ['byDomain' => $byDomain];
        });
    }

    private function allCacheKey(): string
    {
        return 'config_parameters:values';
    }

    /**
     * Clear all registered definitions.
     * Used for testing purposes.
     */
    public static function clearDefinitions(): void
    {
        self::$definitions = [];
    }
}
