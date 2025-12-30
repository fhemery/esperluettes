<?php

namespace App\Domains\Settings\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Settings\Private\Services\SettingsRegistryService;
use App\Domains\Settings\Public\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsRegistryService $registry,
        private SettingsService $settingsService,
        private AuthPublicApi $auth,
    ) {}

    public function index(Request $request)
    {
        $userId = Auth::id();
        $userRoles = $this->getUserRoles($userId);

        $tabs = $this->getVisibleTabs($userRoles);

        if (empty($tabs)) {
            return view('settings::pages.index', [
                'tabs' => [],
                'activeTab' => null,
                'sections' => [],
            ]);
        }

        // Get first tab or requested tab
        $activeTabId = $request->query('tab', $tabs[0]->id ?? null);
        $activeTab = $this->registry->getTab($activeTabId) ?? $tabs[0] ?? null;

        $sections = $activeTab ? $this->getSectionsWithParameters($activeTab->id, $userId, $userRoles) : [];

        return view('settings::pages.index', [
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'sections' => $sections,
        ]);
    }

    public function tab(Request $request, string $tabId)
    {
        $userId = Auth::id();
        $userRoles = $this->getUserRoles($userId);

        $tab = $this->registry->getTab($tabId);
        if (!$tab) {
            abort(404);
        }

        // Check if user can see this tab
        if (!$this->isTabVisibleForRoles($tabId, $userRoles)) {
            abort(403);
        }

        $sections = $this->getSectionsWithParameters($tabId, $userId, $userRoles);

        return view('settings::partials.tab-content', [
            'tab' => $tab,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, string $tabId, string $key): JsonResponse
    {
        $userId = Auth::id();
        $userRoles = $this->getUserRoles($userId);

        $param = $this->registry->getParameter($tabId, $key);
        if (!$param) {
            return response()->json(['message' => 'Parameter not found'], 404);
        }

        // Check role access
        if (!empty($param->roles) && !$this->hasAnyRole($userRoles, $param->roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $value = $request->input('value');
            $this->settingsService->setValue($userId, $tabId, $key, $value);

            return response()->json(['success' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reset(Request $request, string $tabId, string $key): JsonResponse
    {
        $userId = Auth::id();
        $userRoles = $this->getUserRoles($userId);

        $param = $this->registry->getParameter($tabId, $key);
        if (!$param) {
            return response()->json(['message' => 'Parameter not found'], 404);
        }

        // Check role access
        if (!empty($param->roles) && !$this->hasAnyRole($userRoles, $param->roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->settingsService->resetToDefault($userId, $tabId, $key);

        return response()->json(['success' => true]);
    }

    /**
     * Get tabs visible to the user based on their roles.
     */
    private function getVisibleTabs(array $userRoles): array
    {
        $allTabs = $this->registry->getAllTabs();
        $visibleTabs = [];

        foreach ($allTabs as $tab) {
            if ($this->isTabVisibleForRoles($tab->id, $userRoles)) {
                $visibleTabs[] = $tab;
            }
        }

        return $visibleTabs;
    }

    /**
     * Check if a tab has at least one visible parameter for the given roles.
     */
    private function isTabVisibleForRoles(string $tabId, array $userRoles): bool
    {
        $parameters = $this->registry->getParametersForTab($tabId);

        foreach ($parameters as $param) {
            if (empty($param->roles) || $this->hasAnyRole($userRoles, $param->roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get sections with their parameters for a tab, filtered by role visibility.
     */
    private function getSectionsWithParameters(string $tabId, int $userId, array $userRoles): array
    {
        $sections = $this->registry->getSectionsForTab($tabId);
        $result = [];

        foreach ($sections as $section) {
            $parameters = $this->registry->getParametersForSection($tabId, $section->id);
            $visibleParams = [];

            foreach ($parameters as $param) {
                if (empty($param->roles) || $this->hasAnyRole($userRoles, $param->roles)) {
                    $isOverridden = $this->settingsService->isOverridden($userId, $tabId, $param->key);
                    $value = $this->settingsService->getValue($userId, $tabId, $param->key);

                    $visibleParams[] = [
                        'definition' => $param,
                        'value' => $value,
                        'isOverridden' => $isOverridden,
                    ];
                }
            }

            // Only include section if it has visible parameters
            if (!empty($visibleParams)) {
                $result[] = [
                    'section' => $section,
                    'parameters' => $visibleParams,
                ];
            }
        }

        return $result;
    }

    /**
     * Check if user has any of the required roles.
     */
    private function hasAnyRole(array $userRoles, array $requiredRoles): bool
    {
        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get role slugs for a user.
     */
    private function getUserRoles(int $userId): array
    {
        $rolesById = $this->auth->getRolesByUserIds([$userId]);
        $roleDtos = $rolesById[$userId] ?? [];

        return array_map(fn ($dto) => $dto->slug, $roleDtos);
    }
}
