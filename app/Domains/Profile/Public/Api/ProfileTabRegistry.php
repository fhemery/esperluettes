<?php

namespace App\Domains\Profile\Public\Api;

use App\Domains\Profile\Public\Contracts\ProfileTabDefinition;
use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;
use InvalidArgumentException;

/**
 * In-memory registry of profile tabs, populated by each owning domain from its
 * own service provider.
 *
 * Bound as a container singleton in ProfileServiceProvider: instance state, no
 * statics, so the registry is rebuilt on every application boot and nothing
 * leaks between tests.
 */
class ProfileTabRegistry
{
    /** @var array<string, ProfileTabDefinition> */
    private array $tabs = [];

    /**
     * @throws InvalidArgumentException if the key is taken, or a second tab claims the default
     */
    public function register(ProfileTabDefinition $tab): void
    {
        if (isset($this->tabs[$tab->key])) {
            throw new InvalidArgumentException("Profile tab '{$tab->key}' is already registered.");
        }

        if ($tab->isDefault && ($existing = $this->defaultTab()) !== null) {
            throw new InvalidArgumentException(
                "Profile tab '{$tab->key}' cannot be the default tab: '{$existing->key}' already is."
            );
        }

        $this->tabs[$tab->key] = $tab;
    }

    /**
     * All registered tabs, whether or not they are visible, sorted by order then key.
     *
     * @return array<int, ProfileTabDefinition>
     */
    public function all(): array
    {
        $tabs = array_values($this->tabs);
        usort($tabs, fn ($a, $b) => [$a->order, $a->key] <=> [$b->order, $b->key]);

        return $tabs;
    }

    public function get(string $key): ?ProfileTabDefinition
    {
        return $this->tabs[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->tabs[$key]);
    }

    /**
     * Tabs this viewer may see, sorted. Single source of truth for both the tab
     * strip and the route guard.
     *
     * @return array<int, ProfileTabDefinition>
     */
    public function visibleFor(int $ownerUserId, ?int $viewerId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (ProfileTabDefinition $tab) => $this->passesVisibility($tab, $ownerUserId, $viewerId)
        ));
    }

    public function isVisible(string $key, int $ownerUserId, ?int $viewerId): bool
    {
        $tab = $this->get($key);

        return $tab !== null && $this->passesVisibility($tab, $ownerUserId, $viewerId);
    }

    /**
     * The tab to land on: the one flagged as default when this viewer may see
     * it, otherwise the first visible tab.
     */
    public function defaultFor(int $ownerUserId, ?int $viewerId): ?ProfileTabDefinition
    {
        $default = $this->defaultTab();

        if ($default !== null && $this->passesVisibility($default, $ownerUserId, $viewerId)) {
            return $default;
        }

        return $this->visibleFor($ownerUserId, $viewerId)[0] ?? null;
    }

    /**
     * Clear every registered tab. Testing only.
     */
    public function clear(): void
    {
        $this->tabs = [];
    }

    private function defaultTab(): ?ProfileTabDefinition
    {
        foreach ($this->all() as $tab) {
            if ($tab->isDefault) {
                return $tab;
            }
        }

        return null;
    }

    private function passesVisibility(ProfileTabDefinition $tab, int $ownerUserId, ?int $viewerId): bool
    {
        $visibility = app($tab->visibility);

        if (! $visibility instanceof ProfileTabVisibility) {
            throw new InvalidArgumentException(
                "Visibility class '{$tab->visibility}' for profile tab '{$tab->key}' must implement "
                . ProfileTabVisibility::class . '.'
            );
        }

        return $visibility->isVisible($ownerUserId, $viewerId);
    }
}
