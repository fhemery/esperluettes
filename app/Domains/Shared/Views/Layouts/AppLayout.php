<?php

namespace App\Domains\Shared\Views\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        // Switch layout depending on authentication status
        if (Auth::check()) {
            // Logged-in users get the full application chrome
            return view('shared::layouts.app');
        }

        // Guests get a minimal layout without the application navigation
        return view('shared::layouts.guest');
    }
}
