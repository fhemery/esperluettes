<?php

namespace App\Domains\Admin\Controllers;

use Illuminate\Http\RedirectResponse;

class FilamentLogoutController
{
    public function __invoke(): RedirectResponse
    {
        // Preserve the original POST semantics (Filament posts to /admin/logout)
        // so that Laravel processes /logout as a POST to our Auth controller
        return redirect(url('/logout'), 307);
    }
}
