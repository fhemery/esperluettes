<?php

declare(strict_types=1);

namespace App\Domains\Administration\Public\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class AdminNavigationRegistry
{
    /** @var array<string, array{label: string, sort_order: int}> */
    private array $groups = [];

    /** @var array<string, array{group: string, label: string, url: string, icon: string, permissions: array<string>, sort_order: int, type: 'filament'|'custom'}> */
    private array $pages = [];

    /**
     * Register a navigation group
     *
     * @param string $key Unique group identifier
     * @param string $label Display label (translatable)
     * @param int $sortOrder Sort order within navigation
     */
    public function registerGroup(string $key, string $label, int $sortOrder = 100): void
    {
        $this->groups[$key] = [
            'label' => $label,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * Register an admin page
     *
     * @param string $key Unique page identifier
     * @param string $group Group key this page belongs to
     * @param string $label Display label (translatable)
     * @param string $url URL (resolved route name or direct URL)
     * @param string $icon Icon identifier (Material Symbols, Heroicons)
     * @param array<string> $permissions Required permissions/roles
     * @param int $sortOrder Sort order within group
     */
    public function registerPage(
        string $key,
        string $group,
        string $label,
        string $url,
        string $icon = '',
        array $permissions = [],
        int $sortOrder = 100,
    ): void {
        $this->pages[$key] = [
            'group' => $group,
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'permissions' => $permissions,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * Get navigation structure for current user
     *
     * @return array<string, array{label: string, sort_order: int, pages: array}>
     */
    public function getNavigation(): array
    {
        $navigation = [];

        foreach ($this->groups as $groupKey => $group) {
            $pages = $this->getPagesForGroup($groupKey);
            
            if ($pages->isNotEmpty()) {
                $navigation[$groupKey] = [
                    'label' => $group['label'],
                    'sort_order' => $group['sort_order'],
                    'pages' => $pages->values()->all(),
                ];
            }
        }

        // Sort groups by sort_order
        uasort($navigation, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $navigation;
    }

    /**
     * Get all pages for a specific group that the current user can access
     */
    private function getPagesForGroup(string $groupKey): Collection
    {
        return collect($this->pages)
            ->filter(fn($page) => $page['group'] === $groupKey)
            ->filter(fn($page) => $this->userCanAccessPage($page))
            ->sortBy('sort_order');
    }

    /**
     * Check if current user can access a page based on permissions
     */
    private function userCanAccessPage(array $page): bool
    {
        if (empty($page['permissions'])) {
            return true;
        }

        /** @var Authenticatable|null $user */
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Check if user has any of the required permissions/roles
        foreach ($page['permissions'] as $permission) {
            // Use method_exists to avoid IDE errors - hasRole is a custom method on User model
            if (method_exists($user, 'hasRole') && $user->hasRole($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all registered groups
     *
     * @return array<string, array{label: string, sort_order: int}>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get all registered pages
     *
     * @return array<string, array{group: string, label: string, url: string, icon: string, permissions: array<string>, sort_order: int, type: 'filament'|'custom'}>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * Clear all registrations (useful for testing)
     */
    public function clear(): void
    {
        $this->groups = [];
        $this->pages = [];
    }
}
