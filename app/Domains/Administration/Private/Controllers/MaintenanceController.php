<?php

namespace App\Domains\Administration\Private\Controllers;

use App\Domains\Shared\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController extends Controller
{
    public function index()
    {
        return view('administration::pages.maintenance');
    }

    public function emptyCache()
    {
         // Single call that clears config, route, view caches, etc.
        Artisan::call('optimize:clear');

        return redirect()->route('administration.maintenance')->with('success', __('administration::maintenance.empty-cache.success'));
    }
}
