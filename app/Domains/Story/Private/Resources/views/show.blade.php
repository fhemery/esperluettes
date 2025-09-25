@section('title', $viewModel->getTitle() . ' – ' . config('app.name'))
@push('meta')
<meta name="description" content="{{ $metaDescription }}">
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $viewModel->getTitle() }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:image" content="{{ asset('images/story/default-cover.svg') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $viewModel->getTitle() }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ asset('images/story/default-cover.svg') }}">
@endpush

<x-app-layout>
    <div class="overflow-hidden surface-read text-on-surface">
        <div class="p-4 grid items-start gap-2 sm: gap-4 grid-cols-[300px_auto_auto] grid-rows-[auto_1fr_auto]">
            <!-- Image -->
            <div
                class="row-span-2 col-span-1 w-[300px] flex flex-col gap-2 items-center justify-center">
                <img src="{{ asset('images/story/default-cover.svg') }}" alt="{{ $viewModel->getTitle() }}" class="w-full h-full object-cover">
                <div class="flex items-center">
                    <x-story::story-visibility-display :visibility="$viewModel->getVisibility()" />
                </div>
            </div>

            <!-- Title -->
            <div class="col-span-2 flex items-center justify-between">
                <h1 class="font-semibold text-2xl leading-tight mr-2">
                    {{ $viewModel->getTitle() }}
                </h1>

                @if($viewModel->isAuthor())
                <div class="flex items-center gap-2">
                    <a href="{{ url('/stories/'.$viewModel->getSlug().'/edit') }}"
                        class="text-indigo-600 hover:text-indigo-800"
                        aria-label="{{ __('story::show.edit') }}"
                        title="{{ __('story::show.edit') }}">
                        <span class="material-symbols-outlined text-accent/80 hover:text-accent">edit</span>
                    </a>
                    <button type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', '{{ 'confirm-delete-story' }}')"
                        aria-label="{{ __('story::show.delete') }}"
                        title="{{ __('story::show.delete') }}">
                        <span class="material-symbols-outlined text-error/80 hover:text-error">delete</span>
                    </button>
                </div>
                @endif
            </div>

            <!-- Author, genres, triggers, status, feedback... -->
            <div class="col-span-1 row-span-1 flex flex-col flex-start gap-2 h-full">
                <div class="flex-1 flex flex-col gap-2">
                    <!-- Authors -->
                    <div>
                        <span class="font-medium">{{ __('story::shared.by') }}
                            <x-profile::inline-names :profiles="$viewModel->authors" />
                        </span>
                    </div>

                    <!-- Genres -->
                    <div>
                        @php($genres = $viewModel->getGenreNames())
                        @if(!empty($genres))
                        <span class="inline-flex flex-wrap gap-2">
                            @foreach($genres as $g)
                            <x-shared::badge
                                color="accent"
                                size="md"
                                title="{{ __('story::shared.genres.label') }}">{{ $g }}</x-shared::badge>
                            @endforeach
                        </span>
                        @endif
                    </div>

                    <!-- Trigger warnings -->
                    <div class="flex flex-col gap-2">
                        <div class="font-semibold text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined">warning</span>
                            {{ __('story::shared.trigger_warnings.label') }}
                        </div>
                        @php($tws = $viewModel->getTriggerWarningNames())
                        @if(!empty($tws))
                        <span class="inline-flex flex-wrap gap-2">
                            @foreach($tws as $tw)
                            <x-shared::badge
                                color="primary"
                                size="md"
                                title="{{ __('story::shared.trigger_warnings.label') }}">{{ $tw }}</x-shared::badge>
                            @endforeach
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Other badges -->
                <div class="text-sm text-gray-700">
                    <span class="inline-flex flex-wrap gap-2">
                        @if($viewModel->getTypeName())
                        <x-shared::popover placement="top">
                            <x-slot name="trigger">
                                <x-shared::badge
                                    color="neutral"
                                    size="sm"
                                    title="{{ __('story::shared.type.label') }}"
                                    icon="category">
                                    {{ $viewModel->getTypeName() }}
                                </x-shared::badge>
                            </x-slot>
                            <div>{{ __('story::shared.type.label') }}</div>
                        </x-shared::popover>
                        @endif
                        @if($viewModel->getAudienceName())
                        <x-shared::popover placement="top">
                            <x-slot name="trigger">
                                <x-shared::badge
                                    color="neutral"
                                    size="sm"
                                    title="{{ __('story::shared.audience.label') }}"
                                    icon="group">
                                    {{ $viewModel->getAudienceName() }}
                                </x-shared::badge>
                            </x-slot>
                            <div>{{ __('story::shared.audience.label') }}</div>
                        </x-shared::popover>
                        @endif
                        @if($viewModel->getCopyrightName())
                        <x-shared::popover placement="top">
                            <x-slot name="trigger">
                                <x-shared::badge
                                    color="neutral"
                                    size="sm"
                                    title="{{ __('story::shared.copyright.label') }}"
                                    icon="copyright">
                                    {{ $viewModel->getCopyrightName() }}
                                </x-shared::badge>
                            </x-slot>
                            <div>{{ __('story::shared.copyright.label') }}</div>
                        </x-shared::popover>
                        @endif
                        @if($viewModel->getStatusName())
                        <x-shared::popover placement="top">
                            <x-slot name="trigger">
                                <x-shared::badge
                                    color="neutral"
                                    size="sm"
                                    title="{{ __('story::shared.status.label') }}"
                                    icon="edit_note">
                                    {{ $viewModel->getStatusName() }}
                                </x-shared::badge>
                            </x-slot>
                            <div>{{ __('story::shared.status.label') }}</div>
                        </x-shared::popover>
                        @endif
                        @if($viewModel->getFeedbackName())
                        <x-shared::popover placement="top">
                            <x-slot name="trigger">
                                <x-shared::badge
                                    color="neutral"
                                    size="sm"
                                    title="{{ __('story::shared.feedback.label') }}"
                                    icon="forum">
                                    {{ $viewModel->getFeedbackName() }}
                                </x-shared::badge>
                            </x-slot>
                            <div>{{ __('story::shared.feedback.label') }}</div>
                        </x-shared::popover>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Summary -->
            <div class="row-span-2 p-4 bg-bg">
                <article class="p-2 prose flex-1 overflow-hidden bg-[#fffafd]">
                    @if(!$viewModel->hasDescription())
                    <p class="italic text-gray-500">{{ __('story::show.no_description') }}</p>
                    @else
                    {!! $viewModel->getDescription() !!}
                    @endif
                </article>
            </div>

            <!-- Stats -->
            <div class="col-span-2 border-t border-fg pt-2 w-full flex items-center justify-center gap-4">
                <x-shared::metric-badge
                    icon="visibility"
                    size="md"
                    :value="$viewModel->getReadsLoggedTotal()"
                    :label="__('story::chapters.reads.label')"
                    :tooltip="__('story::chapters.reads.tooltip')" />

                <x-story::words-metric-badge
                    size="md"
                    :nb-words="$viewModel->getWordsTotal()"
                    :nb-characters="$viewModel->getCharactersTotal()" />
            </div>
        </div>

    </div>

    {{-- Chapters section --}}
    <div class="w-full mb-4">
        @if($viewModel->isAuthor())
        @include('story::chapters.partials.chapter-list.author-view', [
        'story' => $viewModel->story,
        'chapters' => $viewModel->chapters,
        'creditsExhausted' => ($creditsExhausted ?? false),
        ])
        @else
        @include('story::chapters.partials.chapter-list.reader-view', ['story' => $viewModel->story, 'chapters' => $viewModel->chapters])
        @endif
    </div>

    @if($viewModel->isAuthor())
    <x-shared::confirm-modal
        name="confirm-delete-story"
        :title="__('story::show.delete_confirm_title')"
        :body="__('story::show.delete_confirm_body')"
        :cancel="__('story::show.cancel')"
        :confirm="__('story::show.confirm_delete')"
        :action="route('stories.destroy', ['slug' => $viewModel->getSlug()])"
        method="DELETE"
        maxWidth="md" />
    @endif
</x-app-layout>