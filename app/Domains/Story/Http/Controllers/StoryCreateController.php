<?php

namespace App\Domains\Story\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Domains\StoryRef\Services\StoryRefLookupService;

class StoryCreateController
{
    public function __construct(
        private readonly StoryRefLookupService $lookup,
    ) {}

    /**
     * Display the story creation page.
     */
    public function create(Request $request): View
    {
        $types = $this->lookup->getTypes();
        return view('story::create', [
            'types' => $types,
        ]);
    }
}
