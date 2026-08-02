@php
    use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;

    /** @var \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\MyQuotesViewModel $model */

    $date = fn ($value) => $value?->isoFormat('LLL') ?? '';

    // One template for every phase (architecture §4): the banner and the
    // presence of the picker are the only things that vary.
    $banner = match ($model->phase) {
        QuoteContestPhase::BeforeStart => $model->submissionsStartAt
            ? __('quote-contest::quote-contest.phase.before_start', ['date' => $date($model->submissionsStartAt)])
            : __('quote-contest::quote-contest.phase.before_start_undated'),
        QuoteContestPhase::Submissions => __('quote-contest::quote-contest.phase.submissions', ['date' => $date($model->submissionsEndAt)]),
        QuoteContestPhase::Interlude => __('quote-contest::quote-contest.phase.interlude', ['date' => $date($model->votesStartAt)]),
        QuoteContestPhase::Voting => __('quote-contest::quote-contest.phase.voting'),
        QuoteContestPhase::Ended => __('quote-contest::quote-contest.phase.ended'),
    };
@endphp

<div class="flex flex-col gap-6 qc-my-quotes">
    <p class="surface-read rounded-lg p-4 text-sm">{{ $banner }}</p>

    @if(! $model->hasCategories())
        <p class="surface-read rounded-lg p-4 text-sm">
            {{ __('quote-contest::quote-contest.my_quotes.no_categories') }}
        </p>
    @else
        <section class="flex flex-col gap-3">
            <x-shared::title tag="h2" icon="category">
                {{ __('quote-contest::quote-contest.my_quotes.categories_title') }}
            </x-shared::title>

            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 list-none p-0">
                @foreach($model->categories as $category)
                    <li class="surface-read rounded-lg p-4 flex flex-col gap-2">
                        <h3 class="font-semibold">{{ $category->title }}</h3>

                        @if($category->description)
                            <p class="text-sm text-fg/70">{{ $category->description }}</p>
                        @endif

                        @if($category->myEntry)
                            <div class="border-l-2 border-accent pl-3 flex flex-col gap-1">
                                <p class="text-xs uppercase tracking-wide text-fg/60">
                                    {{ __('quote-contest::quote-contest.my_quotes.your_entry') }}
                                </p>
                                <blockquote class="text-sm italic">{{ $category->myEntry->highlightedText }}</blockquote>
                                <p class="text-xs text-fg/70">
                                    <a href="{{ $category->myEntry->storyUrl }}" class="underline">{{ $category->myEntry->storyTitle }}</a>
                                    —
                                    <a href="{{ $category->myEntry->chapterUrl }}" class="underline">{{ $category->myEntry->chapterTitle }}</a>
                                </p>
                            </div>
                        @else
                            <p class="text-sm text-fg/60">
                                {{ __('quote-contest::quote-contest.my_quotes.no_entry') }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        @if($model->isSubmissionPhase())
            <section class="flex flex-col gap-3">
                <x-shared::title tag="h2" icon="format_quote">
                    {{ __('quote-contest::quote-contest.my_quotes.picker_title') }}
                </x-shared::title>

                @if($model->quotes === [])
                    <p class="surface-read rounded-lg p-4 text-sm">
                        {{ __('quote-contest::quote-contest.my_quotes.picker_empty') }}
                    </p>
                @else
                    {{-- Decision #21: the whole quote book is rendered once and
                         filtered client-side, so typing costs no round trip. --}}
                    <div x-data="{ filter: '' }" class="flex flex-col gap-3">
                        <div>
                            <x-shared::input-label for="qc_quote_filter">
                                {{ __('quote-contest::quote-contest.my_quotes.filter_label') }}
                            </x-shared::input-label>
                            <x-shared::text-input id="qc_quote_filter" type="search" x-model="filter"
                                class="mt-1 block w-full"
                                :placeholder="__('quote-contest::quote-contest.my_quotes.filter_placeholder')" />
                        </div>

                        <ul class="flex flex-col gap-3 list-none p-0">
                            @foreach($model->quotes as $quote)
                                <li
                                    data-search="{{ $quote->searchable() }}"
                                    x-show="filter === '' || $el.dataset.search.includes(filter.toLowerCase())"
                                    @unless($quote->isEligible()) aria-disabled="true" @endunless
                                    class="rounded-lg p-4 flex flex-col gap-2 {{ $quote->isEligible() ? 'surface-read' : 'surface-read opacity-60' }}"
                                >
                                    <blockquote class="text-sm italic">{{ $quote->highlightedText }}</blockquote>

                                    <p class="text-xs text-fg/70">
                                        @if($quote->storyUrl)
                                            <a href="{{ $quote->storyUrl }}" class="underline">{{ $quote->storyTitle }}</a>
                                        @else
                                            {{ $quote->storyTitle }}
                                        @endif
                                        @if($quote->chapterTitle)
                                            —
                                            @if($quote->chapterUrl)
                                                <a href="{{ $quote->chapterUrl }}" class="underline">{{ $quote->chapterTitle }}</a>
                                            @else
                                                {{ $quote->chapterTitle }}
                                            @endif
                                        @endif
                                    </p>

                                    @unless($quote->isEligible())
                                        {{-- The reason is text, not colour alone (spec §6). --}}
                                        <p class="text-xs font-medium">
                                            {{ __('quote-contest::quote-contest.my_quotes.ineligible_prefix') }}
                                            @if($quote->ineligibilityReason === \App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService::REASON_EXCLUDED_FROM_EVENTS)
                                                {{ __('quote-contest::quote-contest.ineligible.excluded_from_events') }}
                                            @else
                                                {{ __('quote-contest::quote-contest.ineligible.private_story') }}
                                            @endif
                                        </p>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        @endif
    @endif
</div>
