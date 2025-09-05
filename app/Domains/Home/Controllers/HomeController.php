<?php

namespace App\Domains\Home\Controllers;

use App\Domains\Shared\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Guests see the home page; authenticated users are redirected to their dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('home::index');
    }
}
