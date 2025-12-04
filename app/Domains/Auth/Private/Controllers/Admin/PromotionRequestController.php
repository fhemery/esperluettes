<?php

declare(strict_types=1);

namespace App\Domains\Auth\Private\Controllers\Admin;

use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Private\Services\PromotionRequestService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionRequestController extends Controller
{
    public function __construct(
        private readonly PromotionRequestService $promotionService,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->input('search');
        
        // If searching, first find matching profiles by display name
        $userIds = null;
        if (!empty($search)) {
            $matchingProfiles = $this->profileApi->searchDisplayNames($search, 100);
            $userIds = array_keys($matchingProfiles);
            
            // If no profiles match, return empty results
            if (empty($userIds)) {
                $userIds = [0]; // Force no results
            }
        }

        $filters = [
            'status' => $request->input('status', 'pending'),
            'user_ids' => $userIds,
        ];

        $requests = $this->promotionService->getPaginatedRequests($filters, 20);

        // Enrich with profile data
        $resultUserIds = $requests->pluck('user_id')->unique()->toArray();
        $profiles = $this->profileApi->getPublicProfiles($resultUserIds);

        return view('auth::pages.admin.promotion-requests.index', [
            'requests' => $requests,
            'profiles' => $profiles,
            'filters' => ['status' => $filters['status'], 'search' => $search],
        ]);
    }

    public function accept(PromotionRequest $promotionRequest): RedirectResponse
    {
        $adminId = Auth::id();

        $success = $this->promotionService->acceptRequest($promotionRequest->id, $adminId);

        if (!$success) {
            return redirect()
                ->route('auth.admin.promotion-requests.index')
                ->with('error', __('auth::admin.promotion.accept_error'));
        }

        return redirect()
            ->route('auth.admin.promotion-requests.index')
            ->with('success', __('auth::admin.promotion.accepted'));
    }

    public function reject(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $adminId = Auth::id();

        $success = $this->promotionService->rejectRequest(
            $promotionRequest->id,
            $adminId,
            $validated['rejection_reason']
        );

        if (!$success) {
            return redirect()
                ->route('auth.admin.promotion-requests.index')
                ->with('error', __('auth::admin.promotion.reject_error'));
        }

        return redirect()
            ->route('auth.admin.promotion-requests.index')
            ->with('success', __('auth::admin.promotion.rejected'));
    }
}
