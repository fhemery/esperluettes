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

{{-- One Alpine scope for the whole tab: the categories above need to know
     which quote the reader picked in the list below. --}}
<div class="flex flex-col gap-6 qc-my-quotes"
    @if($model->isSubmissionPhase()) x-data="{ filter: '', selectedQuote: null, selectedText: '' }" @endif
>
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

                        @if($model->isSubmissionPhase())
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <p class="text-xs text-fg/60" x-show="!selectedQuote">
                                    {{ __('quote-contest::quote-contest.my_quotes.pick_one_first') }}
                                </p>

                                @if($category->myEntry)
                                    {{-- Replacing is destructive, so it goes through a
                                         confirmation modal — never a JS confirm(). --}}
                                    <x-shared::button type="button" color="primary" icon="swap_horiz"
                                        x-cloak x-show="selectedQuote"
                                        x-on:click="$dispatch('open-modal', 'qc-replace-{{ $category->id }}')">
                                        {{ __('quote-contest::quote-contest.my_quotes.replace') }}
                                    </x-shared::button>

                                    <form method="POST"
                                          action="{{ route('quote-contest.entries.destroy', [$model->activityId, $category->myEntry->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-shared::button type="submit" color="neutral" outline icon="close">
                                            {{ __('quote-contest::quote-contest.my_quotes.withdraw') }}
                                        </x-shared::button>
                                    </form>
                                @else
                                    <form method="POST" x-cloak x-show="selectedQuote"
                                          action="{{ route('quote-contest.entries.store', $model->activityId) }}">
                                        @csrf
                                        <input type="hidden" name="category_id" value="{{ $category->id }}">
                                        <input type="hidden" name="quote_id" x-bind:value="selectedQuote">
                                        <x-shared::button type="submit" color="primary" icon="send">
                                            {{ __('quote-contest::quote-contest.my_quotes.submit') }}
                                        </x-shared::button>
                                    </form>
                                @endif
                            </div>

                            @if($category->myEntry)
                                {{-- Outside every <form> above: nesting them is illegal
                                     HTML. `focusable` moves focus inside on open, so
                                     the keyboard does not stay behind the overlay. --}}
                                <x-shared::modal name="qc-replace-{{ $category->id }}" maxWidth="md" focusable>
                                    <div class="p-6 flex flex-col gap-3">
                                        <x-shared::title tag="h2">
                                            {{ __('quote-contest::quote-contest.my_quotes.replace_confirm_title') }}
                                        </x-shared::title>

                                        <p class="text-sm">
                                            {{ __('quote-contest::quote-contest.my_quotes.replace_confirm_body', ['category' => $category->title]) }}
                                        </p>
                                        <blockquote class="border-l-2 border-accent pl-3 text-sm italic">
                                            {{ $category->myEntry->highlightedText }}
                                        </blockquote>

                                        <p class="text-sm">
                                            {{ __('quote-contest::quote-contest.my_quotes.replace_confirm_new') }}
                                        </p>
                                        <blockquote class="border-l-2 border-primary pl-3 text-sm italic"
                                                    x-text="selectedText"></blockquote>

                                        <div class="mt-3 flex justify-end gap-3">
                                            <x-shared::button type="button" color="neutral" outline
                                                x-on:click="$dispatch('close-modal', 'qc-replace-{{ $category->id }}')">
                                                {{ __('quote-contest::quote-contest.my_quotes.replace_confirm_cancel') }}
                                            </x-shared::button>

                                            <form method="POST"
                                                  action="{{ route('quote-contest.entries.store', $model->activityId) }}">
                                                @csrf
                                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                                <input type="hidden" name="quote_id" x-bind:value="selectedQuote">
                                                <x-shared::button type="submit" color="danger" icon="swap_horiz">
                                                    {{ __('quote-contest::quote-contest.my_quotes.replace_confirm_confirm') }}
                                                </x-shared::button>
                                            </form>
                                        </div>
                                    </div>
                                </x-shared::modal>
                            @endif
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
                    <div class="flex flex-col gap-3">
                        <div>
                            <x-shared::input-label for="qc_quote_filter">
                                {{ __('quote-contest::quote-contest.my_quotes.filter_label') }}
                            </x-shared::input-label>
                            <x-shared::text-input id="qc_quote_filter" type="search" x-model="filter"
                                class="mt-1 block w-full"
                                :placeholder="__('quote-contest::quote-contest.my_quotes.filter_placeholder')" />
                        </div>

                        {{-- A radio group: one choice among N, keyboard operable
                             and state-announcing for free. The legend names the
                             group when a screen reader enters it; it is hidden
                             visually because the section heading already says
                             it on screen. --}}
                        <fieldset class="border-0 p-0 m-0">
                            <legend class="sr-only">
                                {{ __('quote-contest::quote-contest.my_quotes.picker_legend') }}
                            </legend>

                        <ul class="flex flex-col gap-3 list-none p-0">
                            @foreach($model->quotes as $quote)
                                <li
                                    data-search="{{ $quote->searchable() }}"
                                    x-show="filter === '' || $el.dataset.search.includes(filter.toLowerCase())"
                                    @unless($quote->isEligible()) aria-disabled="true" @endunless
                                    class="rounded-lg p-4 flex flex-col gap-2 {{ $quote->isEligible() ? 'surface-read' : 'surface-read opacity-60' }}"
                                >
                                    {{-- An ineligible row carries no reachable
                                         control, so it cannot be picked at all,
                                         and points at the reason below. --}}
                                    <label class="flex items-start gap-2 {{ $quote->isEligible() ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                                        <input type="radio" name="qc_selected_quote" class="mt-1"
                                            value="{{ $quote->id }}"
                                            @unless($quote->isEligible())
                                                disabled aria-disabled="true"
                                                aria-describedby="qc-quote-reason-{{ $quote->id }}"
                                            @endunless
                                            x-on:change="selectedQuote = {{ $quote->id }}; selectedText = @js($quote->highlightedText)"
                                        />
                                        <span class="sr-only">{{ __('quote-contest::quote-contest.my_quotes.select_quote') }}</span>
                                        <blockquote class="text-sm italic">{{ $quote->highlightedText }}</blockquote>
                                    </label>

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
                                        {{-- The reason is text, not colour alone: read
                                             on screen, and tied to the disabled radio
                                             through `aria-describedby`. --}}
                                        <p class="text-xs font-medium" id="qc-quote-reason-{{ $quote->id }}">
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
                        </fieldset>
                    </div>
                @endif
            </section>
        @endif
    @endif
</div>
