<?php

namespace App\Domains\Notification\Private\Controllers;

use App\Domains\Notification\Private\Services\NotificationPreferencesService;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationPreferencesController extends Controller
{
    public function __construct(
        private NotificationPreferencesService $prefsService,
        private NotificationFactory $factory,
        private NotificationChannelRegistry $channelRegistry,
    ) {}

    public function save(Request $request): RedirectResponse
    {
        $userId   = Auth::id();
        $submitted = $request->input('prefs', []);
        $channels  = $this->channelRegistry->getActiveChannels();

        foreach ($this->factory->getGroups() as $group) {
            foreach ($this->factory->getTypesForGroup($group->id) as $typeDef) {
                if (!$typeDef->forcedOnWebsite) {
                    $enabled = (bool) ($submitted[$typeDef->type]['website'] ?? false);
                    $this->prefsService->set($userId, $typeDef->type, 'website', $enabled);
                }
                foreach ($channels as $channel) {
                    $enabled = (bool) ($submitted[$typeDef->type][$channel->id] ?? false);
                    $this->prefsService->set($userId, $typeDef->type, $channel->id, $enabled);
                }
            }
        }

        return redirect()->route('settings.index', ['tab' => 'notification'])
            ->with('success', __('notifications::settings.saved'));
    }

    public function update(string $type, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $channel = $validated['channel'];
        $enabled = (bool) $validated['enabled'];

        $channelError = $this->validateChannel($channel);
        if ($channelError !== null) {
            return $channelError;
        }

        $typeDef = $this->factory->getTypeDefinition($type);
        if ($typeDef === null || $typeDef->hideInSettings) {
            return response()->json(['message' => 'Type not found'], 404);
        }

        if ($channel === 'website' && $typeDef->forcedOnWebsite) {
            return response()->json(['message' => 'This notification type cannot be disabled on website'], 403);
        }

        $this->prefsService->set(Auth::id(), $type, $channel, $enabled);

        return response()->json(['success' => true]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|string',
            'enabled' => 'required|boolean',
            'scope'   => 'required|string',
        ]);

        $channel = $validated['channel'];
        $enabled = (bool) $validated['enabled'];
        $scope   = $validated['scope'];

        $channelError = $this->validateChannel($channel);
        if ($channelError !== null) {
            return $channelError;
        }

        if ($scope !== 'all') {
            $groupIds = array_map(fn($g) => $g->id, $this->factory->getGroups());
            if (!in_array($scope, $groupIds, true)) {
                return response()->json(['message' => 'Invalid scope'], 422);
            }
        }

        if ($scope === 'all') {
            $this->prefsService->setAll(Auth::id(), $channel, $enabled);
        } else {
            $this->prefsService->setGroup(Auth::id(), $scope, $channel, $enabled);
        }

        return response()->json(['success' => true]);
    }

    private function validateChannel(string $channel): ?JsonResponse
    {
        if ($channel === 'website') {
            return null;
        }

        $channelDef = $this->channelRegistry->get($channel);
        if ($channelDef === null) {
            return response()->json(['message' => 'Channel not found'], 404);
        }

        $activeIds = array_map(fn($c) => $c->id, $this->channelRegistry->getActiveChannels());
        if (!in_array($channel, $activeIds, true)) {
            return response()->json(['message' => 'Channel not active'], 403);
        }

        return null;
    }
}
