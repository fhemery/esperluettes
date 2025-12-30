<?php

namespace App\Domains\Settings\Private\Services;

use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use InvalidArgumentException;

class SettingsRegistryService
{
    /**
     * In-memory registry of tab definitions.
     *
     * @var array<string, SettingsTabDefinition>
     */
    private static array $tabs = [];

    /**
     * In-memory registry of section definitions.
     * Structure: [tabId => [sectionId => SettingsSectionDefinition]]
     *
     * @var array<string, array<string, SettingsSectionDefinition>>
     */
    private static array $sections = [];

    /**
     * In-memory registry of parameter definitions.
     * Structure: [tabId => [key => SettingsParameterDefinition]]
     *
     * @var array<string, array<string, SettingsParameterDefinition>>
     */
    private static array $parameters = [];

    /**
     * Register a tab.
     *
     * @throws InvalidArgumentException if tab ID already exists
     */
    public function registerTab(SettingsTabDefinition $tab): void
    {
        $tabId = strtolower($tab->id);

        if (isset(self::$tabs[$tabId])) {
            throw new InvalidArgumentException("Settings tab '{$tab->id}' is already registered.");
        }

        self::$tabs[$tabId] = $tab;
    }

    /**
     * Register a section under a tab.
     *
     * @throws InvalidArgumentException if tab does not exist or section ID already exists within tab
     */
    public function registerSection(SettingsSectionDefinition $section): void
    {
        $tabId = strtolower($section->tabId);
        $sectionId = strtolower($section->id);

        if (!isset(self::$tabs[$tabId])) {
            throw new InvalidArgumentException("Cannot register section '{$section->id}': tab '{$section->tabId}' does not exist.");
        }

        if (isset(self::$sections[$tabId][$sectionId])) {
            throw new InvalidArgumentException("Settings section '{$section->id}' is already registered under tab '{$section->tabId}'.");
        }

        self::$sections[$tabId][$sectionId] = $section;
    }

    /**
     * Register a parameter under a section.
     *
     * @throws InvalidArgumentException if tab or section does not exist, or key already exists within tab
     */
    public function registerParameter(SettingsParameterDefinition $param): void
    {
        $tabId = strtolower($param->tabId);
        $sectionId = strtolower($param->sectionId);
        $key = strtolower($param->key);

        if (!isset(self::$tabs[$tabId])) {
            throw new InvalidArgumentException("Cannot register parameter '{$param->key}': tab '{$param->tabId}' does not exist.");
        }

        if (!isset(self::$sections[$tabId][$sectionId])) {
            throw new InvalidArgumentException("Cannot register parameter '{$param->key}': section '{$param->sectionId}' does not exist under tab '{$param->tabId}'.");
        }

        if (isset(self::$parameters[$tabId][$key])) {
            throw new InvalidArgumentException("Settings parameter '{$param->key}' is already registered under tab '{$param->tabId}'.");
        }

        self::$parameters[$tabId][$key] = $param;
    }

    /**
     * Get a tab by ID.
     */
    public function getTab(string $tabId): ?SettingsTabDefinition
    {
        return self::$tabs[strtolower($tabId)] ?? null;
    }

    /**
     * Get all registered tabs, sorted by order.
     *
     * @return array<SettingsTabDefinition>
     */
    public function getAllTabs(): array
    {
        $tabs = array_values(self::$tabs);
        usort($tabs, fn ($a, $b) => $a->order <=> $b->order);

        return $tabs;
    }

    /**
     * Get all sections for a tab, sorted by order.
     *
     * @return array<SettingsSectionDefinition>
     */
    public function getSectionsForTab(string $tabId): array
    {
        $tabId = strtolower($tabId);
        if (!isset(self::$sections[$tabId])) {
            return [];
        }

        $sections = array_values(self::$sections[$tabId]);
        usort($sections, fn ($a, $b) => $a->order <=> $b->order);

        return $sections;
    }

    /**
     * Get a section by tab ID and section ID.
     */
    public function getSection(string $tabId, string $sectionId): ?SettingsSectionDefinition
    {
        return self::$sections[strtolower($tabId)][strtolower($sectionId)] ?? null;
    }

    /**
     * Get all parameters for a section, sorted by order.
     *
     * @return array<SettingsParameterDefinition>
     */
    public function getParametersForSection(string $tabId, string $sectionId): array
    {
        $tabId = strtolower($tabId);
        $sectionId = strtolower($sectionId);

        if (!isset(self::$parameters[$tabId])) {
            return [];
        }

        $params = array_filter(
            self::$parameters[$tabId],
            fn ($p) => strtolower($p->sectionId) === $sectionId
        );

        $params = array_values($params);
        usort($params, fn ($a, $b) => $a->order <=> $b->order);

        return $params;
    }

    /**
     * Get a parameter definition by tab ID and key.
     */
    public function getParameter(string $tabId, string $key): ?SettingsParameterDefinition
    {
        return self::$parameters[strtolower($tabId)][strtolower($key)] ?? null;
    }

    /**
     * Get all parameters for a tab, sorted by order.
     *
     * @return array<SettingsParameterDefinition>
     */
    public function getParametersForTab(string $tabId): array
    {
        $tabId = strtolower($tabId);

        if (!isset(self::$parameters[$tabId])) {
            return [];
        }

        $params = array_values(self::$parameters[$tabId]);
        usort($params, fn ($a, $b) => $a->order <=> $b->order);

        return $params;
    }

    /**
     * Clear all registered definitions.
     * Used for testing purposes.
     */
    public static function clearAll(): void
    {
        self::$tabs = [];
        self::$sections = [];
        self::$parameters = [];
    }
}
