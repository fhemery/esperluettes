<?php

namespace App\Domains\Quote\Private\View\Components;

use App\Domains\Quote\Public\Api\Contracts\QuoteListDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Citations tab of a user's profile.
 *
 * Self-hydrating: the profile page hands it only the owner and whether the
 * viewer owns the profile, and the component loads its own page of quotes.
 */
class ProfileTab extends Component
{
    public QuoteListDto $quoteList;
    public string $profileSlug;
    public bool $isOwn;

    public function __construct(
        private QuotePublicApi $quoteApi,
        private ProfilePublicApi $profileApi,
        public int $ownerUserId,
    ) {
        $viewerId = Auth::id() !== null ? (int) Auth::id() : null;
        $this->isOwn = $viewerId === $this->ownerUserId;
        $page = max(1, (int) request()->query('page', 1));

        $this->quoteList = $this->quoteApi->getForProfile($this->ownerUserId, $viewerId, $page);
        $this->profileSlug = $this->profileApi->getPublicProfile($this->ownerUserId)?->slug ?? '';
    }

    public function render(): ViewContract
    {
        return view('quote::components.profile-tab');
    }
}
