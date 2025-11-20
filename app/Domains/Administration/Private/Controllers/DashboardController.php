<?php

declare(strict_types=1);

namespace App\Domains\Administration\Private\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('administration::pages.dashboard');
    }
}
