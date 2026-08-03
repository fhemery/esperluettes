@php
    /** @var \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\ResultsViewModel $model */
@endphp

{{-- Moderators and admins only: this partial is included solely when the
     component built a results view model, which it does for nobody else.
     No Alpine state of its own — the only script here is the shared modal's
     (architecture §4). --}}
<div class="flex flex-col gap-6 qc-results">
    <p class="surface-read rounded-lg p-4 text-sm">
        {{ __('quote-contest::quote-contest.results.intro') }}
    </p>

    @if(! $model->hasCategories())
        <p class="surface-read rounded-lg p-4 text-sm">
            {{ __('quote-contest::quote-contest.results.no_categories') }}
        </p>
    @else
        @foreach($model->categories as $category)
            <section class="flex flex-col gap-3">
                <x-shared::title tag="h2" icon="leaderboard">{{ $category->title }}</x-shared::title>

                @if($category->isEmpty())
                    <p class="surface-read rounded-lg p-4 text-sm">
                        {{ __('quote-contest::quote-contest.results.no_entries') }}
                    </p>
                @else
                    <div class="surface-read rounded-lg overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <caption class="sr-only">{{ $category->title }}</caption>
                            <thead>
                                <tr class="border-b border-fg/20">
                                    <th scope="col" class="p-3">{{ __('quote-contest::quote-contest.results.entry') }}</th>
                                    <th scope="col" class="p-3">{{ __('quote-contest::quote-contest.results.vote_count') }}</th>
                                    <th scope="col" class="p-3">{{ __('quote-contest::quote-contest.results.submitter') }}</th>
                                    <th scope="col" class="p-3">
                                        <span class="sr-only">{{ __('quote-contest::quote-contest.results.actions') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->entries as $entry)
                                    <tr class="border-b border-fg/10 align-top {{ $entry->isWithdrawn ? 'opacity-60' : '' }}">
                                        <td class="p-3">
                                            <blockquote class="italic">{{ $entry->highlightedText }}</blockquote>

                                            <p class="text-xs text-fg/70 mt-1">
                                                <a href="{{ $entry->storyUrl }}" class="underline">{{ $entry->storyTitle }}</a>
                                                —
                                                <a href="{{ $entry->chapterUrl }}" class="underline">{{ $entry->chapterTitle }}</a>
                                            </p>

                                            @if($entry->hasAuthorNames())
                                                <p class="text-xs text-fg/70">
                                                    {{ __('quote-contest::quote-contest.votes.authors_by') }}
                                                    {{ implode(', ', $entry->authorNames) }}
                                                </p>
                                            @endif

                                            @if($entry->isWithdrawn)
                                                {{-- Tradeoff 3: a privacy-withdrawn entry still shows
                                                     here, so moderation can see what happened. --}}
                                                <p class="mt-1">
                                                    <x-shared::badge color="neutral" icon="visibility_off">
                                                        {{ __('quote-contest::quote-contest.results.withdrawn') }}
                                                    </x-shared::badge>
                                                </p>
                                            @endif
                                        </td>

                                        <td class="p-3 font-semibold tabular-nums">{{ $entry->voteCount }}</td>

                                        <td class="p-3">
                                            @if($entry->hasSubmitter())
                                                <a href="{{ $entry->submitterUrl }}" class="underline">{{ $entry->submitterName }}</a>
                                            @else
                                                {{-- Decision #7: the entry outlives its submitter. --}}
                                                <span class="text-fg/60">{{ __('quote-contest::quote-contest.results.unknown_submitter') }}</span>
                                            @endif
                                        </td>

                                        <td class="p-3">
                                            <x-shared::button type="button" color="danger" outline icon="delete"
                                                x-on:click="$dispatch('open-modal', 'qc-delete-{{ $entry->id }}')">
                                                {{ __('quote-contest::quote-contest.results.delete') }}
                                            </x-shared::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Outside the table: a fixed-position dialog has no business
                         inside a <td>, and the form must not nest in one either. --}}
                    @foreach($category->entries as $entry)
                        <x-shared::modal name="qc-delete-{{ $entry->id }}" maxWidth="md">
                            <div class="p-6 flex flex-col gap-3">
                                <x-shared::title tag="h2">
                                    {{ __('quote-contest::quote-contest.results.delete_confirm_title') }}
                                </x-shared::title>

                                <p class="text-sm">
                                    {{ __('quote-contest::quote-contest.results.delete_confirm_body', ['category' => $category->title]) }}
                                </p>
                                <blockquote class="border-l-2 border-accent pl-3 text-sm italic">
                                    {{ $entry->highlightedText }}
                                </blockquote>

                                <div class="mt-3 flex justify-end gap-3">
                                    <x-shared::button type="button" color="neutral" outline
                                        x-on:click="$dispatch('close-modal', 'qc-delete-{{ $entry->id }}')">
                                        {{ __('quote-contest::quote-contest.results.delete_confirm_cancel') }}
                                    </x-shared::button>

                                    <form method="POST"
                                          action="{{ route('quote-contest.moderation.entries.destroy', [$model->activityId, $entry->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-shared::button type="submit" color="danger" icon="delete">
                                            {{ __('quote-contest::quote-contest.results.delete_confirm_confirm') }}
                                        </x-shared::button>
                                    </form>
                                </div>
                            </div>
                        </x-shared::modal>
                    @endforeach
                @endif
            </section>
        @endforeach
    @endif
</div>
