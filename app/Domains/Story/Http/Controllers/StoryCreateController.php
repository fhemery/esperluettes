<?php

namespace App\Domains\Story\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StoryCreateController
{
    /**
     * Display the story creation page.
     */
    public function create(Request $request): View
    {
        return view('story::create');
    }
}
