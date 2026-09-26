<?php

namespace App\Domains\Config\Public\Api;

use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleDefinition;
use App\Domains\Config\Public\Services\ConfigParameterService;
use App\Domains\Config\Public\Services\FeatureToggleService;

class ConfigPublicApi
{
    public function __construct(
        private FeatureToggleService $featureToggleService,
        private ConfigParameterService $parameterService,
    ) {}

    // =========================================================================
    // Feature Toggles
    // =========================================================================

    /**
     * Declare a feature toggle. Call from your ServiceProvider boot().
     * Checking an undeclared toggle throws UndeclaredFeatureToggleException.
     */
    public function registerFeatureToggle(FeatureToggleDefinition $definition): void
    {
        $this->featureToggleService->registerFeatureToggle($definition);
    }

    public function isToggleEnabled(string $featureToggleName, ?string $domain = 'config'): bool
    {
        return $this->featureToggleService->isToggleEnabled($featureToggleName, $domain);
    }

    /**
     * Set the state of a declared toggle; the first change creates its row.
     * $roles === null keeps the current roles.
     */
    public function updateFeatureToggle(string $featureToggleName, FeatureToggleAccess $access, ?string $domain = 'config', ?array $roles = null): void
    {
        $this->featureToggleService->updateFeatureToggle($featureToggleName, $access, $domain, $roles);
    }

    /**
     * Delete an orphan row (tech admin only). Throws DomainException for a declared toggle.
     */
    public function deleteFeatureToggle(string $featureToggleName, ?string $domain = 'config'): void
    {
        $this->featureToggleService->deleteFeatureToggle($featureToggleName, $domain);
    }

    /**
     * List all feature toggles visible to the current admin/tech admin.
     *
     * @return array<FeatureToggle>
     */
    public function listFeatureToggles(): array
    {
        return $this->featureToggleService->listFeatureToggles();
    }

    /**
     * List stored rows that no declaration matches. Tech admins only (empty otherwise).
     *
     * @return array<FeatureToggle>
     */
    public function listOrphanFeatureToggles(): array
    {
        return $this->featureToggleService->listOrphanFeatureToggles();
    }

    // =========================================================================
    // Configuration Parameters (Public API for other domains)
    // =========================================================================

    /**
     * Register a parameter definition.
     * Called from domain ServiceProviders during boot().
     */
    public function registerParameter(ConfigParameterDefinition $definition): void
    {
        $this->parameterService->registerParameter($definition);
    }

    /**
     * Get current value for a parameter.
     * Returns default if no override exists.
     * Returns null if parameter not registered.
     */
    public function getParameterValue(string $key, string $domain): mixed
    {
        return $this->parameterService->getParameterValue($key, $domain);
    }
}
