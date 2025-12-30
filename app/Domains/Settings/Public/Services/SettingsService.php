<?php

namespace App\Domains\Settings\Public\Services;

use App\Domains\Settings\Private\Repositories\SettingRepository;
use App\Domains\Settings\Private\Services\SettingsRegistryService;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * In-memory cache of user settings for the current request.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $requestCache = [];

    public function __construct(
        private SettingRepository $repository,
        private SettingsRegistryService $registry,
    ) {}

    /**
     * Get current value for a parameter for a specific user.
     * Returns default if no override exists.
     * Returns null if parameter not registered.
     */
    public function getValue(int $userId, string $tabId, string $key): mixed
    {
        $definition = $this->registry->getParameter($tabId, $key);
        if (!$definition) {
            return null;
        }

        $cached = $this->getUserSettingsCached($userId);
        $cacheKey = $this->buildCacheKey($tabId, $key);

        if (isset($cached[$cacheKey])) {
            return $definition->cast($cached[$cacheKey]);
        }

        return $definition->default;
    }

    /**
     * Convenience: get value for currently authenticated user.
     * Returns default if not authenticated or parameter not registered.
     */
    public function getValueForCurrentUser(string $tabId, string $key): mixed
    {
        $userId = Auth::id();
        if (!$userId) {
            $definition = $this->registry->getParameter($tabId, $key);

            return $definition?->default;
        }

        return $this->getValue($userId, $tabId, $key);
    }

    /**
     * Set a parameter value for a specific user.
     * If value equals default, the stored override is removed.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setValue(int $userId, string $tabId, string $key, mixed $value): void
    {
        $definition = $this->registry->getParameter($tabId, $key);
        if (!$definition) {
            return;
        }

        $definition->validate($value);

        // If value equals default, remove the override
        if ($this->valuesEqual($value, $definition->default, $definition)) {
            $this->resetToDefault($userId, $tabId, $key);

            return;
        }

        $this->repository->upsert([
            'user_id' => $userId,
            'domain' => strtolower($tabId),
            'key' => strtolower($key),
            'value' => $definition->serialize($value),
        ]);

        $this->invalidateCache($userId);
    }

    /**
     * Convenience: set value for currently authenticated user.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setValueForCurrentUser(string $tabId, string $key, mixed $value): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $this->setValue($userId, $tabId, $key, $value);
    }

    /**
     * Reset a parameter to its default value (removes stored override).
     */
    public function resetToDefault(int $userId, string $tabId, string $key): void
    {
        $this->repository->deleteByUserDomainAndKey(
            $userId,
            strtolower($tabId),
            strtolower($key)
        );

        $this->invalidateCache($userId);
    }

    /**
     * Convenience: reset for currently authenticated user.
     */
    public function resetToDefaultForCurrentUser(string $tabId, string $key): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $this->resetToDefault($userId, $tabId, $key);
    }

    /**
     * Check if a parameter is overridden (not using default) for a user.
     */
    public function isOverridden(int $userId, string $tabId, string $key): bool
    {
        $cached = $this->getUserSettingsCached($userId);
        $cacheKey = $this->buildCacheKey($tabId, $key);

        return isset($cached[$cacheKey]);
    }

    /**
     * Get all parameter values for a user within a tab.
     *
     * @return array<array{definition: SettingsParameterDefinition, value: mixed, isOverridden: bool}>
     */
    public function getParametersWithValuesForTab(int $userId, string $tabId): array
    {
        $parameters = $this->registry->getParametersForTab($tabId);
        $cached = $this->getUserSettingsCached($userId);
        $result = [];

        foreach ($parameters as $definition) {
            $cacheKey = $this->buildCacheKey($tabId, $definition->key);
            $isOverridden = isset($cached[$cacheKey]);

            $value = $isOverridden
                ? $definition->cast($cached[$cacheKey])
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
     * Get cached settings for a user.
     * Loads from cache or database on first access.
     *
     * @return array<string, string>
     */
    private function getUserSettingsCached(int $userId): array
    {
        // Check request-level cache first
        if (isset($this->requestCache[$userId])) {
            return $this->requestCache[$userId];
        }

        // Load from persistent cache
        $cached = Cache::remember(
            $this->cacheKey($userId),
            null, // Forever, invalidated explicitly
            function () use ($userId) {
                $settings = $this->repository->getAllForUser($userId);
                $result = [];

                foreach ($settings as $setting) {
                    $key = $this->buildCacheKey($setting->domain, $setting->key);
                    $result[$key] = $setting->value;
                }

                return $result;
            }
        );

        $this->requestCache[$userId] = $cached;

        return $cached;
    }

    /**
     * Invalidate cache for a user.
     */
    private function invalidateCache(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
        unset($this->requestCache[$userId]);
    }

    /**
     * Build cache key for a user.
     */
    private function cacheKey(int $userId): string
    {
        return "user_settings:{$userId}";
    }

    /**
     * Build a composite key for tab.key lookup.
     */
    private function buildCacheKey(string $tabId, string $key): string
    {
        return strtolower($tabId).'.'.strtolower($key);
    }

    /**
     * Compare two values for equality, considering the parameter type.
     */
    private function valuesEqual(mixed $a, mixed $b, SettingsParameterDefinition $definition): bool
    {
        $castA = $definition->cast($a);
        $castB = $definition->cast($b);

        return $castA === $castB;
    }
}
