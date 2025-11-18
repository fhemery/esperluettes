<?php

declare(strict_types=1);

namespace App\Domains\Moderation\Public\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class AdminUserManagementComponent extends Component
{
    public function __construct(private readonly AuthPublicApi $authApi)
    {
    }

    public function render()
    {
        if (! Auth::check() || ! $this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR])) {
            throw new AuthorizationException('You are not authorized to view this component.');
        }

        return view('moderation::components.admin-user-management');
    }
}
