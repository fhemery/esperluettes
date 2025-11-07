<?php

namespace App\Domains\ReadList\Private\View\Components;

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Services\ReadListService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ReadListToggleComponent extends Component
{
    public bool $shouldRender = false;
    public bool $isInReadList = false;

    public function __construct(
        public int $storyId,
        public bool $isAuthor,
        private ReadListService $readListService
    ) {
        $this->hydrate();
    }

    private function hydrate(): void
    {
        $user = Auth::user();

        // Don't render if not authenticated
        if (!$user) {
            return;
        }

        // Don't render if user is author
        if ($this->isAuthor) {
            return;
        }

        // Don't render if user doesn't have USER or USER_CONFIRMED role
        if (!$user->hasRole(Roles::USER) && !$user->hasRole(Roles::USER_CONFIRMED)) {
            return;
        }

        // All checks passed, component should render
        $this->shouldRender = true;

        // Check if story is in read list
        $this->isInReadList = $this->readListService->hasStory($user->id, $this->storyId);
    }

    public function render(): ViewContract
    {
        return view('read-list::components.read-list-toggle', [
            'shouldRender' => $this->shouldRender,
            'isInReadList' => $this->isInReadList,
            'storyId' => $this->storyId,
        ]);
    }
}
