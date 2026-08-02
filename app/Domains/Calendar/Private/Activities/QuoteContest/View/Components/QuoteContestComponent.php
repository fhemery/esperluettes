<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Components;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class QuoteContestComponent extends Component
{
    public function __construct(
        public Activity $activity,
    ) {}

    public function render(): View
    {
        return view('quote-contest::components.quote-contest', [
            'activity' => $this->activity,
        ]);
    }
}
