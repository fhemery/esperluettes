<?php

namespace App\Domains\Story\Controllers;

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
        $referentials = $this->lookup->getStoryReferentials();
        return view('story::create', [
            'referentials' => $referentials,
        ]);
    }
}
