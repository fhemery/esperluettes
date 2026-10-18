<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Requests\ReviewFanartRequest;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FanartReviewController
{
    public function __construct(private readonly FanartService $service)
    {
    }

    public function accept(ReviewFanartRequest $request): RedirectResponse
    {
        $fanart = $request->fanart();
        if ($fanart === null || $fanart->status !== FanartStatus::Pending) {
            return $this->alreadyDecided();
        }

        $this->service->accept($fanart, Auth::id(), (string) $request->validated('comment'));

        return back()->with('success', __('fanart::ui.review.accepted'));
    }

    public function decline(ReviewFanartRequest $request): RedirectResponse
    {
        $fanart = $request->fanart();
        if ($fanart === null || $fanart->status !== FanartStatus::Pending) {
            return $this->alreadyDecided();
        }

        $this->service->decline($fanart, Auth::id(), (string) $request->validated('comment'));

        return back()->with('success', __('fanart::ui.review.declined'));
    }

    private function alreadyDecided(): RedirectResponse
    {
        return back()->with('error', __('fanart::ui.review.already_decided'));
    }
}
