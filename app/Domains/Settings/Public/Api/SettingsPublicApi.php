<?php

namespace App\Domains\Settings\Public\Api;

use App\Domains\Settings\Private\Services\SettingsRegistryService;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Settings\Public\Services\SettingsService;

class SettingsPublicApi
{
    public function __construct(
        private SettingsRegistryService $registry,
        private SettingsService $settingsService,
    ) {}

    // =========================================================================
    // Registration (called from ServiceProvider::boot())
    // =========================================================================

    /**
     * Register a tab. Tabs must be registered before sections.
     *
     * @throws \InvalidArgumentException if tab ID already exists
     */
    public function registerTab(SettingsTabDefinition $tab): void
    {
        $this->registry->registerTab($tab);
    }

    /**
     * Register a section under a tab.
     *
     * @throws \InvalidArgumentException if tab does not exist or section ID already exists
     */
    public function registerSection(SettingsSectionDefinition $section): void
    {
        $this->registry->registerSection($section);
    }

    /**
     * Register a parameter under a section.
     *
     * @throws \InvalidArgumentException if tab or section does not exist, or key already exists
     */
    public function registerParameter(SettingsParameterDefinition $param): void
    {
        $this->registry->registerParameter($param);
    }

    // =========================================================================
    // Value Access (for other domains)
    // =========================================================================

    /**
     * Get current value for a parameter for a specific user.
     * Returns default if no override exists.
     * Returns null if parameter not registered.
     */
    public function getValue(int $userId, string $tabId, string $key): mixed
    {
        return $this->settingsService->getValue($userId, $tabId, $key);
    }

    /**
     * Convenience: get value for currently authenticated user.
     * Returns default if not authenticated or parameter not registered.
     */
    public function getValueForCurrentUser(string $tabId, string $key): mixed
    {
        return $this->settingsService->getValueForCurrentUser($tabId, $key);
    }

    /**
     * Set a parameter value for a specific user.
     * If value equals default, the stored override is removed.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setValue(int $userId, string $tabId, string $key, mixed $value): void
    {
        $this->settingsService->setValue($userId, $tabId, $key, $value);
    }

    /**
     * Convenience: set value for currently authenticated user.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setValueForCurrentUser(string $tabId, string $key, mixed $value): void
    {
        $this->settingsService->setValueForCurrentUser($tabId, $key, $value);
    }

    /**
     * Reset a parameter to its default value (removes stored override).
     */
    public function resetToDefault(int $userId, string $tabId, string $key): void
    {
        $this->settingsService->resetToDefault($userId, $tabId, $key);
    }

    /**
     * Convenience: reset for currently authenticated user.
     */
    public function resetToDefaultForCurrentUser(string $tabId, string $key): void
    {
        $this->settingsService->resetToDefaultForCurrentUser($tabId, $key);
    }

    // =========================================================================
    // Registry Access (for UI rendering)
    // =========================================================================

    /**
     * Get a tab by ID.
     */
    public function getTab(string $tabId): ?SettingsTabDefinition
    {
        return $this->registry->getTab($tabId);
    }

    /**
     * Get all registered tabs, sorted by order.
     *
     * @return array<SettingsTabDefinition>
     */
    public function getAllTabs(): array
    {
        return $this->registry->getAllTabs();
    }

    /**
     * Get all sections for a tab, sorted by order.
     *
     * @return array<SettingsSectionDefinition>
     */
    public function getSectionsForTab(string $tabId): array
    {
        return $this->registry->getSectionsForTab($tabId);
    }

    /**
     * Get all parameters for a section, sorted by order.
     *
     * @return array<SettingsParameterDefinition>
     */
    public function getParametersForSection(string $tabId, string $sectionId): array
    {
        return $this->registry->getParametersForSection($tabId, $sectionId);
    }

    /**
     * Get a parameter definition by tab ID and key.
     */
    public function getParameter(string $tabId, string $key): ?SettingsParameterDefinition
    {
        return $this->registry->getParameter($tabId, $key);
    }
}
