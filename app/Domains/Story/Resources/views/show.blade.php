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
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center">
                    <h1 class="font-semibold text-2xl text-gray-900 leading-tight mr-2">
                        {{ $viewModel->getTitle() }}
                    </h1>
                    <x-story::story-visibility-badge :visibility="$viewModel->getVisibility()" />
                </div>
                @if($viewModel->isAuthor())
                <div class="flex items-center gap-3">
                    <a href="{{ url('/stories/'.$viewModel->getSlug().'/edit') }}"
                        class="text-indigo-600 hover:text-indigo-800"
                        aria-label="{{ __('story::show.edit') }}"
                        title="{{ __('story::show.edit') }}">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <button type="button"
                        class="text-red-600 hover:text-red-800"
                        x-data
                        x-on:click="$dispatch('open-modal', '{{ 'confirm-delete-story' }}')"
                        aria-label="{{ __('story::show.delete') }}"
                        title="{{ __('story::show.delete') }}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
                @endif
            </div>

            <div class="flex items-center justify-between mb-6 text-sm text-gray-600">
                <div>
                    <span class="font-medium">{{ __('story::shared.by') }}
                        <x-profile::inline-names :profiles="$viewModel->authors" />
                    </span>
                </div>
                <div>
                    @php($date = app()->getLocale() === 'fr' ? $viewModel->getCreatedAt()->format('d-m-Y') : $viewModel->getCreatedAt()->format('Y-m-d'))
                    <span>{{ $date }}</span>
                </div>
            </div>


            <div class="flex gap-6">
                <div class="shrink-0" aria-hidden="true">
                    <div
                        class="w-[150px] h-[200px] rounded-lg border-4 border-[#ACE1AF] flex items-center justify-center bg-white">
                        <span
                            class="text-[90px] leading-none font-serif text-[#ACE1AF] select-none">&amp;</span>
                    </div>
                </div>
                <div class="max-w-none flex flex-col">
                    <!-- Two-column badges (Genres / Trigger Warnings) above summary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            @php($genres = $viewModel->getGenreNames())
                            @if(!empty($genres))
                            <span class="inline-flex flex-wrap gap-2">
                                @foreach($genres as $g)
                                <span
                                    class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20"
                                    title="{{ __('story::shared.genres.label') }}">{{ $g }}</span>
                                @endforeach
                            </span>
                            @endif
                        </div>
                        <div>
                            @php($tws = $viewModel->getTriggerWarningNames())
                            @if(!empty($tws))
                            <span class="inline-flex flex-wrap gap-2">
                                @foreach($tws as $tw)
                                <span
                                    class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20"
                                    title="{{ __('story::shared.trigger_warnings.label') }}">{{ $tw }}</span>
                                @endforeach
                            </span>
                            @endif
                        </div>
                    </div>

                    <!-- Total Reads, centered below badges -->
                    <div class="mb-2">
                        <div class="w-full">
                            <x-shared::metric-badge
                                icon="visibility"
                                :value="$viewModel->getReadsLoggedTotal()"
                                :label="__('story::chapters.reads.label')"
                                :tooltip="__('story::chapters.reads.tooltip')"
                            />

                            <x-story::words-metric-badge
                                class="ml-2"
                                :nb-words="$viewModel->getWordsTotal()"
                                :nb-characters="$viewModel->getCharactersTotal()"
                            />
                        </div>
                    </div>

                    <article class="prose flex-1">
                        <h2>{{ __('story::shared.description.label') }}</h2>
                        @if(!$viewModel->hasDescription())
                        <p class="italic text-gray-500">{{ __('story::show.no_description') }}</p>
                        @else
                        {!! $viewModel->getDescription() !!}
                        @endif
                    </article>

                    <!-- Other attributes badges below summary -->
                    <div class="mt-6 text-sm text-gray-700">
                        <span class="inline-flex flex-wrap gap-2">
                            @if($viewModel->getTypeName())
                            <x-shared::popover placement="top" maxWidth="16rem">
                                <x-slot name="trigger">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                                        <span class="material-symbols-outlined text-[16px] leading-none">category</span>
                                        {{ $viewModel->getTypeName() }}
                                    </span>
                                </x-slot>
                                <div class="font-semibold text-gray-900">{{ __('story::shared.type.label') }}</div>
                            </x-shared::popover>
                            @endif
                            @if($viewModel->getAudienceName())
                            <x-shared::popover placement="top" maxWidth="16rem">
                                <x-slot name="trigger">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                                        <span class="material-symbols-outlined text-[16px] leading-none">group</span>
                                        {{ $viewModel->getAudienceName() }}
                                    </span>
                                </x-slot>
                                <div class="font-semibold text-gray-900">{{ __('story::shared.audience.label') }}</div>
                            </x-shared::popover>
                            @endif
                            @if($viewModel->getCopyrightName())
                            <x-shared::popover placement="top" maxWidth="16rem">
                                <x-slot name="trigger">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                                        <span class="material-symbols-outlined text-[16px] leading-none">copyright</span>
                                        {{ $viewModel->getCopyrightName() }}
                                    </span>
                                </x-slot>
                                <div class="font-semibold text-gray-900">{{ __('story::shared.copyright.label') }}</div>
                            </x-shared::popover>
                            @endif
                            @if($viewModel->getStatusName())
                            <x-shared::popover placement="top" maxWidth="16rem">
                                <x-slot name="trigger">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                                        <span class="material-symbols-outlined text-[16px] leading-none">edit_note</span>
                                        {{ $viewModel->getStatusName() }}
                                    </span>
                                </x-slot>
                                <div class="font-semibold text-gray-900">{{ __('story::shared.status.label') }}</div>
                            </x-shared::popover>
                            @endif
                            @if($viewModel->getFeedbackName())
                            <x-shared::popover placement="top" maxWidth="16rem">
                                <x-slot name="trigger">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                                        <span class="material-symbols-outlined text-[16px] leading-none">forum</span>
                                        {{ $viewModel->getFeedbackName() }}
                                    </span>
                                </x-slot>
                                <div class="font-semibold text-gray-900">{{ __('story::shared.feedback.label') }}</div>
                            </x-shared::popover>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chapters section --}}
    <div class="w-full mb-4">
        @if($viewModel->isAuthor())
        @include('story::chapters.partials.chapter-list.author-view', ['story' => $viewModel->story, 'chapters' => $viewModel->chapters])
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