@props(['activity' => null])

@php
    $configService = app(\App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestConfigService::class);

    $settings = $activity ? $configService->settingsFor($activity->id) : null;
    $categories = $activity ? $configService->categoriesFor($activity->id) : collect();

    $asInput = fn ($date) => $date?->format('Y-m-d\TH:i') ?? '';
    $submissionsEndAt = old('quote_contest.submissions_end_at', $asInput($settings?->submissions_end_at));
    $votesStartAt = old('quote_contest.votes_start_at', $asInput($settings?->votes_start_at));
@endphp

<div class="surface-bg p-6 rounded-lg flex flex-col gap-4 quote-contest-config">
    <h2 class="text-base font-semibold">{{ __('quote-contest::quote-contest.config.section_title') }}</h2>
    <p class="text-xs text-fg/60">{{ __('quote-contest::quote-contest.config.timeline_hint') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Greyed mirrors of the activity's own dates, live-bound because the
             admin is editing those very fields a section above. --}}
        <div x-data="{ mirrored: '' }"
             x-init="const source = document.getElementById('active_starts_at');
                     mirrored = source?.value ?? '';
                     source?.addEventListener('input', () => mirrored = source.value)">
            <x-shared::input-label for="qc_submissions_start_at">
                {{ __('quote-contest::quote-contest.config.submissions_start_at') }}
            </x-shared::input-label>
            <x-shared::text-input id="qc_submissions_start_at" type="datetime-local"
                class="mt-1 block w-full opacity-60 cursor-not-allowed"
                x-bind:value="mirrored" disabled aria-disabled="true" />
            <p class="text-xs text-fg/60 mt-1">{{ __('quote-contest::quote-contest.config.mirrored_hint') }}</p>
        </div>

        <div>
            <x-shared::input-label for="qc_submissions_end_at" :required="true">
                {{ __('quote-contest::quote-contest.config.submissions_end_at') }}
            </x-shared::input-label>
            <x-shared::text-input id="qc_submissions_end_at" name="quote_contest[submissions_end_at]"
                type="datetime-local" class="mt-1 block w-full" :value="$submissionsEndAt" />
            <x-shared::input-error :messages="$errors->get('quote_contest.submissions_end_at')" class="mt-1" />
        </div>

        <div>
            <x-shared::input-label for="qc_votes_start_at" :required="true">
                {{ __('quote-contest::quote-contest.config.votes_start_at') }}
            </x-shared::input-label>
            <x-shared::text-input id="qc_votes_start_at" name="quote_contest[votes_start_at]"
                type="datetime-local" class="mt-1 block w-full" :value="$votesStartAt" />
            <x-shared::input-error :messages="$errors->get('quote_contest.votes_start_at')" class="mt-1" />
        </div>

        <div x-data="{ mirrored: '' }"
             x-init="const source = document.getElementById('active_ends_at');
                     mirrored = source?.value ?? '';
                     source?.addEventListener('input', () => mirrored = source.value)">
            <x-shared::input-label for="qc_votes_end_at">
                {{ __('quote-contest::quote-contest.config.votes_end_at') }}
            </x-shared::input-label>
            <x-shared::text-input id="qc_votes_end_at" type="datetime-local"
                class="mt-1 block w-full opacity-60 cursor-not-allowed"
                x-bind:value="mirrored" disabled aria-disabled="true" />
            <p class="text-xs text-fg/60 mt-1">{{ __('quote-contest::quote-contest.config.mirrored_hint') }}</p>
        </div>
    </div>

    @unless($activity)
        <p class="text-sm text-fg/70">{{ __('quote-contest::quote-contest.config.categories_after_save') }}</p>
    @endunless
</div>

@if($activity)
    {{--
      Categories are managed by their own three routes, so each row needs its own
      <form>. This panel renders *inside* the activity form, and nested forms are
      illegal HTML — browsers silently drop the inner one and its buttons submit
      the activity instead. The block is therefore pushed to a stack the activity
      pages render after </form>.
    --}}
    @push('activity-config-extras')
        <div class="surface-bg p-6 rounded-lg flex flex-col gap-4 max-w-3xl">
            <h2 class="text-base font-semibold">{{ __('quote-contest::quote-contest.config.categories_title') }}</h2>

            @if($categories->isEmpty())
                <p class="text-sm text-fg/70">{{ __('quote-contest::quote-contest.config.categories_empty') }}</p>
            @endif

            @foreach($categories as $category)
                <div class="border border-border rounded-md p-4 flex flex-col gap-3">
                    <form method="POST"
                          action="{{ route('calendar.admin.quote-contest.categories.update', [$activity->id, $category->id]) }}"
                          class="flex flex-col gap-3">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_6rem] gap-3">
                            <div>
                                <x-shared::input-label for="qc_cat_title_{{ $category->id }}" :required="true">
                                    {{ __('quote-contest::quote-contest.config.category_title') }}
                                </x-shared::input-label>
                                <x-shared::text-input id="qc_cat_title_{{ $category->id }}" name="title" type="text"
                                    class="mt-1 block w-full" maxlength="160" required :value="$category->title" />
                            </div>
                            <div>
                                <x-shared::input-label for="qc_cat_position_{{ $category->id }}">
                                    {{ __('quote-contest::quote-contest.config.category_position') }}
                                </x-shared::input-label>
                                <x-shared::text-input id="qc_cat_position_{{ $category->id }}" name="position"
                                    type="number" min="0" max="1000" class="mt-1 block w-full"
                                    :value="$category->position" />
                            </div>
                        </div>

                        <div>
                            <x-shared::input-label for="qc_cat_description_{{ $category->id }}">
                                {{ __('quote-contest::quote-contest.config.category_description') }}
                            </x-shared::input-label>
                            <textarea id="qc_cat_description_{{ $category->id }}" name="description" rows="2"
                                maxlength="2000"
                                class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface">{{ $category->description }}</textarea>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-fg/60">
                                {{ __('quote-contest::quote-contest.config.category_entries_count', ['count' => $category->entries_count]) }}
                            </p>
                            <x-shared::button type="submit" color="primary" icon="save">
                                {{ __('quote-contest::quote-contest.config.save_category') }}
                            </x-shared::button>
                        </div>
                    </form>

                    <div class="flex justify-end">
                        <button type="button" class="text-sm text-error underline"
                                x-data x-on:click="$dispatch('open-modal', 'qc-cat-delete-{{ $category->id }}')">
                            {{ __('quote-contest::quote-contest.config.delete_category') }}
                        </button>
                    </div>

                    <x-shared::confirm-modal
                        name="qc-cat-delete-{{ $category->id }}"
                        :title="__('quote-contest::quote-contest.config.delete_confirm_title')"
                        :body="__('quote-contest::quote-contest.config.delete_confirm_body', ['title' => $category->title])"
                        :cancel="__('quote-contest::quote-contest.config.delete_confirm_cancel')"
                        :confirm="__('quote-contest::quote-contest.config.delete_confirm_confirm')"
                        :action="route('calendar.admin.quote-contest.categories.destroy', [$activity->id, $category->id])"
                        method="DELETE"
                    />
                </div>
            @endforeach

            <form method="POST"
                  action="{{ route('calendar.admin.quote-contest.categories.store', $activity->id) }}"
                  class="border border-dashed border-border rounded-md p-4 flex flex-col gap-3">
                @csrf
                <h3 class="text-sm font-semibold">{{ __('quote-contest::quote-contest.config.add_category_title') }}</h3>

                <div>
                    <x-shared::input-label for="qc_new_cat_title" :required="true">
                        {{ __('quote-contest::quote-contest.config.category_title') }}
                    </x-shared::input-label>
                    <x-shared::text-input id="qc_new_cat_title" name="title" type="text"
                        class="mt-1 block w-full" maxlength="160" required :value="old('title')" />
                    <x-shared::input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-shared::input-label for="qc_new_cat_description">
                        {{ __('quote-contest::quote-contest.config.category_description') }}
                    </x-shared::input-label>
                    <textarea id="qc_new_cat_description" name="description" rows="2" maxlength="2000"
                        class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface">{{ old('description') }}</textarea>
                    <x-shared::input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <x-shared::button type="submit" color="primary" icon="add">
                        {{ __('quote-contest::quote-contest.config.add_category') }}
                    </x-shared::button>
                </div>
            </form>
        </div>
    @endpush
@endif
