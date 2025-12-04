<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function index(ConfigPublicApi $config, AuthPublicApi $authApi): ViewContract
    {
        $calendarEnabled = $config->isToggleEnabled('enabled', 'calendar');
        
        $isConfirmed = $authApi->hasAnyRole([Roles::USER_CONFIRMED]);

        return view('dashboard::index', [
            'calendarEnabled' => $calendarEnabled,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    public function requestPromotion(
        Request $request,
        AuthPublicApi $authApi,
        CommentPublicApi $commentApi
    ): RedirectResponse {
        $userId = $request->user()->id;
        $commentCount = $commentApi->countRootCommentsByUser('chapter', $userId);

        $result = $authApi->requestPromotion($userId, $commentCount);

        if ($result->success) {
            return redirect()->route('dashboard')->with('success', __('dashboard::promotion.success_message'));
        }

        return redirect()->route('dashboard')->withErrors([
            'promotion' => __('dashboard::promotion.errors.request_failed'),
        ]);
    }
}
