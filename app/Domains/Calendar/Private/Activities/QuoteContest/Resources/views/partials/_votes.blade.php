@php
    use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;

    /** @var \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VotesViewModel $model */

    $date = fn ($value) => $value?->isoFormat('LLL') ?? '';

    // One template for every phase (architecture §4): the banner and whether
    // the fieldsets are enabled are the only things that vary.
    $banner = match ($model->phase) {
        QuoteContestPhase::BeforeStart,
        QuoteContestPhase::Submissions,
        QuoteContestPhase::Interlude => $model->votesStartAt
            ? __('quote-contest::quote-contest.votes.opens_at', ['date' => $date($model->votesStartAt)])
            : __('quote-contest::quote-contest.votes.opens_undated'),
        QuoteContestPhase::Voting => $model->votesEndAt
            ? __('quote-contest::quote-contest.votes.open_until', ['date' => $date($model->votesEndAt)])
            : __('quote-contest::quote-contest.votes.open_undated'),
        QuoteContestPhase::Ended => __('quote-contest::quote-contest.votes.closed'),
    };
@endphp

<div class="flex flex-col gap-6 qc-votes">
    <p class="surface-read rounded-lg p-4 text-sm">{{ $banner }}</p>

    @if(! $model->hasBallot())
        {{-- Before the votes open there is deliberately nothing here: the
             ballot is not built, so the entries are not sent either. --}}
        @if(in_array($model->phase, [QuoteContestPhase::Voting, QuoteContestPhase::Ended], true))
            <p class="surface-read rounded-lg p-4 text-sm">
                {{ __('quote-contest::quote-contest.votes.no_categories') }}
            </p>
        @endif
    @else
        @foreach($model->categories as $category)
            {{-- One form per category: the ballot resource is "this reader's
                 choice here", and it is replaced wholesale. --}}
            <form method="POST" action="{{ route('quote-contest.votes.update', [$model->activityId, $category->id]) }}">
                @csrf
                @method('PUT')

                {{-- A fieldset with a radio group: the accessible shape for one
                     choice among N — keyboard operable, state announced, no
                     hand-rolled ARIA. Disabled outside the vote phase, which
                     makes the whole group read-only in one attribute. --}}
                <fieldset class="surface-read rounded-lg p-4 flex flex-col gap-3"
                    @unless($model->isOpen()) disabled @endunless
                >
                    <legend class="font-semibold px-1">{{ $category->title }}</legend>

                    <p>
                        <x-shared::badge :color="$category->hasVoted() ? 'primary' : 'neutral'" :icon="$category->hasVoted() ? 'how_to_vote' : 'pending'">
                            {{ $category->hasVoted()
                                ? __('quote-contest::quote-contest.votes.voted')
                                : __('quote-contest::quote-contest.votes.not_voted') }}
                        </x-shared::badge>
                    </p>

                    @if($category->description)
                        <p class="text-sm text-fg/70">{{ $category->description }}</p>
                    @endif

                    @if($category->isEmpty())
                        <p class="text-sm text-fg/60">
                            {{ __('quote-contest::quote-contest.votes.no_entries') }}
                        </p>
                    @else
                        {{-- Already shuffled for this reader (decision #22): the
                             order is stable across reloads, so nothing moves
                             under the cursor. --}}
                        <ul class="flex flex-col gap-3 list-none p-0">
                            @foreach($category->entries as $entry)
                                <li class="rounded-lg p-3 sm:p-4 flex flex-col gap-2 surface-read">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="radio" class="mt-1"
                                            name="entry_id"
                                            value="{{ $entry->id }}"
                                            @checked($category->myVoteEntryId === $entry->id)
                                        />
                                        <span class="sr-only">{{ __('quote-contest::quote-contest.votes.choose_entry') }}</span>
                                        <blockquote class="text-sm italic">{{ $entry->highlightedText }}</blockquote>
                                    </label>

                                    <p class="text-xs text-fg/70">
                                        <a href="{{ $entry->storyUrl }}" class="underline">{{ $entry->storyTitle }}</a>
                                        —
                                        <a href="{{ $entry->chapterUrl }}" class="underline">{{ $entry->chapterTitle }}</a>
                                    </p>

                                    @if($entry->hasAuthorNames())
                                        {{-- Resolved live, never frozen in the row
                                             (decision #19). These are the story's
                                             authors — never the submitter. --}}
                                        <p class="text-xs text-fg/70">
                                            {{ __('quote-contest::quote-contest.votes.authors_by') }}
                                            {{ implode(', ', $entry->authorNames) }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if($model->isOpen())
                            <div class="flex justify-end">
                                <x-shared::button type="submit" color="primary" icon="how_to_vote">
                                    {{ $category->hasVoted()
                                        ? __('quote-contest::quote-contest.votes.change')
                                        : __('quote-contest::quote-contest.votes.cast') }}
                                </x-shared::button>
                            </div>
                        @endif
                    @endif
                </fieldset>
            </form>
        @endforeach
    @endif
</div>
