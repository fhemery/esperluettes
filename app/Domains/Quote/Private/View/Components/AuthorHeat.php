<?php

namespace App\Domains\Quote\Private\View\Components;

use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Alpine root around the chapter article, hosting the heat tint and its
 * md+ margin markers.
 *
 * Gated on the same policy as the badge and the endpoint. For everybody else it
 * renders its slot and nothing else, so a reader's page carries no heat root.
 */
class AuthorHeat extends Component
{
    public bool $canView;

    /**
     * The marker's accessible label, rendered once per plural form with a
     * `{count}` placeholder the component fills in — trans_choice cannot run in
     * the browser.
     *
     * @var array{one: string, other: string}
     */
    public array $markerLabels = ['one' => '', 'other' => ''];

    public function __construct(
        private QuotePublicApi $quoteApi,
        public int $chapterId,
    ) {
        $viewerId = Auth::id() !== null ? (int) Auth::id() : 0;
        $this->canView = $viewerId > 0 && $this->quoteApi->canViewChapterAggregate($this->chapterId, $viewerId);

        if ($this->canView) {
            $this->markerLabels = [
                'one' => trans_choice('quote::ui.author_marker.label', 1, ['count' => '{count}']),
                'other' => trans_choice('quote::ui.author_marker.label', 2, ['count' => '{count}']),
            ];
        }
    }

    public function render(): ViewContract
    {
        return view('quote::components.author-heat');
    }
}
