<?php

namespace App\Domains\Moderation\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Moderation\Private\Services\ModerationService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ModerationAdminController extends Controller
{
    public function __construct(
        private ProfilePublicApi $profileApi,
        private AuthPublicApi $authApi,
        private ModerationService $moderationService,
    ) {
    }

    public function userManagementPage()
    {
        return view('moderation::pages.admin.user-management');
    }

   
    public function search(Request $request)
    {
        $query = (string) $request->query('q', '');
        $trimmed = trim($query);

        if (mb_strlen($trimmed) < 2) {
            return view('moderation::components.admin-user-management-results', [
                'message' => __('moderation::admin.user_management.min_chars_instruction'),
                'users' => [],
            ]);
        }

        $displayNames = $this->profileApi->searchDisplayNames($trimmed, 20);

        if (empty($displayNames)) {
            return view('moderation::components.admin-user-management-results', [
                'message' => __('moderation::admin.user_management.no_results'),
                'users' => [],
            ]);
        }

        $userIds = array_keys($displayNames);

        $authUsers = $this->authApi->getUsersById($userIds);
        $reportCounts = $this->moderationService->getReportCountsByUserIds($userIds);

        $rows = [];
        foreach ($displayNames as $userId => $displayName) {
            if (! isset($authUsers[$userId])) {
                continue;
            }

            $authData = $authUsers[$userId];
            $counts = $reportCounts[$userId] ?? ['confirmed' => 0, 'rejected' => 0];

            $rows[] = [
                'id' => $userId,
                'display_name' => $displayName,
                'email' => $authData['email'],
                'is_active' => $authData['isActive'],
                'confirmed' => $counts['confirmed'],
                'rejected' => $counts['rejected'],
            ];
        }

        usort($rows, function (array $a, array $b) {
            return strcasecmp($a['display_name'], $b['display_name']);
        });

        $rows = array_slice($rows, 0, 20);

        return view('moderation::components.admin-user-management-results', [
            'message' => null,
            'users' => $rows,
        ]);
    }
}
