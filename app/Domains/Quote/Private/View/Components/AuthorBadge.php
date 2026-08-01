<?php

namespace App\Domains\Quote\Private\View\Components;

use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Author-only « n citations » badge of a chapter, with the heat toggle beside it.
 *
 * Gated on the very policy the aggregate endpoint uses, never on the page's
 * own $isAuthor flag: the badge and the endpoint can then never disagree about
 * who may see this.
 */
class AuthorBadge extends Component
{
    public bool $canView;
    public int $count = 0;

    public function __construct(
        private QuotePublicApi $quoteApi,
        public int $chapterId,
    ) {
        $viewerId = Auth::id() !== null ? (int) Auth::id() : 0;
        $this->canView = $viewerId > 0 && $this->quoteApi->canViewChapterAggregate($this->chapterId, $viewerId);

        if ($this->canView) {
            $this->count = $this->quoteApi->countForChapter($this->chapterId);
        }
    }

    public function render(): ViewContract
    {
        return view('quote::components.author-badge');
    }
}
