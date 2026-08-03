@php
    /** @var \App\Domains\Calendar\Private\Models\Activity $activity */
    /** @var array<int, array{key: string, label: string}> $tabs */
    /** @var \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\MyQuotesViewModel $myQuotes */
    /** @var \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VotesViewModel $votes */
@endphp

<div class="quote-contest-activity">
    {{-- `tracking` puts the active tab in the URL hash, so a notification can
         deep-link straight to it. --}}
    <x-shared::tabs :tabs="$tabs" initial="my-quotes" color="primary" scrollable tracking>
        <div x-show="tab === 'my-quotes'" x-cloak class="mt-6">
            @include('quote-contest::partials._my-quotes', ['model' => $myQuotes])
        </div>

        <div x-show="tab === 'votes'" x-cloak class="mt-6">
            @include('quote-contest::partials._votes', ['model' => $votes])
        </div>
    </x-shared::tabs>
</div>
