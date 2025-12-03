<?php

namespace App\Domains\Config\Public\Api;

use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
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

    public function addFeatureToggle(FeatureToggle $featureToggle): void
    {
        $this->featureToggleService->addFeatureToggle($featureToggle);
    }

    public function isToggleEnabled(string $featureToggleName, ?string $domain = 'config'): bool
    {
        return $this->featureToggleService->isToggleEnabled($featureToggleName, $domain);
    }

    public function updateFeatureToggle(string $featureToggleName, FeatureToggleAccess $access, ?string $domain = 'config'): void
    {
        $this->featureToggleService->updateFeatureToggle($featureToggleName, $access, $domain);
    }

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
