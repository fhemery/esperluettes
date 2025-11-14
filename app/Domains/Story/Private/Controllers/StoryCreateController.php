<?php

namespace App\Domains\Story\Private\Controllers;

use Illuminate\Contracts\View\View;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;

class StoryCreateController
{
    public function __construct(
        private readonly StoryRefPublicApi $storyRefs,
    ) {}

    /**
     * Display the story creation page.
     */
    public function create(): View
    {
        $refsDto = $this->storyRefs->getAllStoryReferentials();
        $referentials = [
            'types' => $refsDto->types->map(fn ($dto) => $dto->toArray()),
            'audiences' => $refsDto->audiences->map(fn ($dto) => $dto->toArray()),
            'copyrights' => $refsDto->copyrights->map(fn ($dto) => $dto->toArray()),
            'genres' => $refsDto->genres->map(fn ($dto) => $dto->toArray()),
            'statuses' => $refsDto->statuses->map(fn ($dto) => $dto->toArray()),
            'trigger_warnings' => $refsDto->triggerWarnings->map(fn ($dto) => $dto->toArray()),
            'feedbacks' => $refsDto->feedbacks->map(fn ($dto) => $dto->toArray()),
        ];
        return view('story::create', [
            'referentials' => $referentials,
        ]);
    }
}
