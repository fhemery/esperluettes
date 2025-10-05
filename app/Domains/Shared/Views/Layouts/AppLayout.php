<?php

namespace App\Domains\Shared\Views\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Domains\Shared\ViewModels\PageViewModel;

class AppLayout extends Component
{
    public function __construct(
        public readonly ?PageViewModel $page = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        // Switch layout depending on authentication status
        if (Auth::check()) {
            // Logged-in users get the full application chrome
            return view('shared::layouts.app', [
                'page' => $this->page,
            ]);
        }

        // Guests get a minimal layout without the application navigation
        return view('shared::layouts.guest', [
            'page' => $this->page,
        ]);
    }
}
