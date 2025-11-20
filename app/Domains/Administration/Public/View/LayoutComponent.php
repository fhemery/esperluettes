<?php

namespace App\Domains\Administration\Public\View;

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class LayoutComponent extends Component
{
    public function render()
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            throw new \Exception('Authentication required');
        }

        $user = Auth::user();
        
        // Check if user has required admin roles
        if (!$user->hasRole([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new \Exception('Insufficient permissions');
        }

        return view('administration::layouts.layout');
    }
}

