@section('title', $viewModel->getTitle() . ' – ' . config('app.name'))
@push('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $viewModel->getTitle() }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $viewModel->coverUrl }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $viewModel->getTitle() }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $viewModel->coverUrl }}">
@endpush

<x-app-layout :page="$page">
    <div class="overflow-hidden surface-read text-on-surface">
        <div
            class="p-2 md:p-4 grid items-start gap-2 md:gap-4 
            grid-cols-[150px_1fr]
            lg:grid-rows-[auto_auto_auto_1fr_auto]
            lg:grid-cols-[300px_1fr_2fr]">

            <!-- Title -->
            <div class="col-span-2 lg:col-start-1 lg:col-end-4 flex items-center justify-between">
                <div class="flex flex-wrap sm:flex-no-wrap flex-1 items-center gap-2">
                    <x-shared::title class="mb-0">
                        <span>{{ $viewModel->getTitle() }}</span>
                    </x-shared::title>
                    <div class="flex">
                    <span class="ml-2">
                        <x-story::story-visibility-display :visibility="$viewModel->getVisibility()" />
                    </span>
                @if ($viewModel->story->is_complete)
                    <x-shared::popover placement="top">
                        <x-slot name="trigger">
                            <x-shared::badge color="success" size="sm" icon="done_all">
                            </x-shared::badge>
                        </x-slot>
                        <div>{{ __('story::show.is_complete.tooltip') }}</div>
                    </x-shared::popover>
                @endif
                @if ($viewModel->story->is_excluded_from_events)
                    <x-shared::popover placement="top">
                        <x-slot name="trigger">
                            <x-shared::badge color="error" size="sm" icon="do_not_disturb_on">
                            </x-shared::badge>
                        </x-slot>
                        <div>{{ __('story::show.is_excluded_from_events.tooltip') }}</div>
                    </x-shared::popover>
                @endif
                </div>
                </div>

                @if ($viewModel->isAuthor())
                    <div class="flex items-center gap-2 sm:mt-0">
                        <a href="{{ route('stories.collaborators.index', ['slug' => $viewModel->getSlug()]) }}"
                            aria-label="{{ __('story::collaborators.manage') }}" title="{{ __('story::collaborators.manage') }}"
                            class="relative">
                            <span class="material-symbols-outlined text-accent/80 hover:text-accent">group</span>
                            @if ($collaboratorCount > 1)
                                <span class="absolute -top-1 -right-1 bg-tertiary text-on-tertiary text-xs rounded-full h-4 w-4 flex items-center justify-center">
                                    {{ $collaboratorCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ url('/stories/' . $viewModel->getSlug() . '/edit') }}"
                            aria-label="{{ __('story::show.edit') }}" title="{{ __('story::show.edit') }}">
                            <span class="material-symbols-outlined text-accent/80 hover:text-accent">edit</span>
                        </a>
                        <button type="button" x-data
                            x-on:click="$dispatch('open-modal', '{{ 'confirm-delete-story' }}')"
                            aria-label="{{ __('story::show.delete') }}" title="{{ __('story::show.delete') }}">
                            <span class="material-symbols-outlined text-error/80 hover:text-error">delete</span>
                        </button>
                    </div>
                @elseif ($betaReaderRole)
                    <x-story::collaborator-badge :role="$betaReaderRole" :story-slug="$viewModel->getSlug()" />
                @endif
            </div>

            <!-- Image + ReadList + Reporting / moderation -->
            <div
                class="col-start-1 col-span-1 row-start-2 row-span-3
                    flex flex-col gap-2 items-center justify-center">
                <x-story::cover class="lg:block hidden" :coverType="$viewModel->coverType" :coverUrl="$viewModel->coverUrl" :coverHdUrl="$viewModel->coverHdUrl" :hd="true" :width="230" />
                <x-story::cover class="lg:hidden block" :coverType="$viewModel->coverType" :coverUrl="$viewModel->coverUrl" :coverHdUrl="$viewModel->coverHdUrl" :hd="true" :width="150" />
                <x-read-list::read-list-toggle-component :story-id="$viewModel->getId()" :is-author="$viewModel->isAuthor()" />
            </div>

            <!-- Author, genres -->
            <div
                class="col-start-2 col-span-1 row-start-2 row-span-1 
                flex flex-col flex-start gap-2 h-full">
                <div class="flex-1 flex flex-col gap-2">
                    <!-- Authors -->
                    <div>
                        <span class="font-medium">{{ __('story::shared.by') }}
                            <x-profile::inline-names :profiles="$viewModel->authors" />
                        </span>
                    </div>

                    <!-- Genres + Other badges but copyright -->
                    <div class="flex flex-col gap-2">
                        @php($genres = $viewModel->getGenreRefs())
                        @if (!empty($genres))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($genres as $g)
                                    <x-story::ref-badge :name="$g->getName()" :description="$g->getDescription()" color="accent"
                                        size="md" />
                                @endforeach


                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($viewModel->getTypeName())
                                    <x-story::ref-badge name="{{ $viewModel->getTypeName() }}"
                                        description="{{ $viewModel->getTypeDescription() }}" color="neutral"
                                        :outline="true" :wrap="true" size="sm" icon="category" />
                                @endif
                                @if ($viewModel->getAudienceName())
                                    <x-story::ref-badge name="{{ $viewModel->getAudienceName() }}"
                                        description="{{ $viewModel->getAudienceDescription() }}" color="neutral"
                                        :outline="true" :wrap="true" size="sm" icon="group" />
                                @endif

                                @if ($viewModel->getStatusName())
                                    <x-story::ref-badge name="{{ $viewModel->getStatusName() }}"
                                        description="{{ $viewModel->getStatusDescription() }}" color="neutral"
                                        :outline="true" :wrap="true" size="sm" icon="edit_note" />
                                @endif
                                @if ($viewModel->getFeedbackName())
                                    <x-story::ref-badge name="{{ $viewModel->getFeedbackName() }}"
                                        description="{{ $viewModel->getFeedbackDescription() }}" color="neutral"
                                        :outline="true" :wrap="true" size="sm" icon="forum" />
                                @endif
                            </div>
                        @endif
                    </div>

                </div>

            </div>

            <!-- Stats -->
            <div
                class="col-start-1 col-span-2 row-start-5 row-span-1
                    md:col-start-2 md:col-span-1 md:row-start-3 md:row-span-1

                border-y border-fg
                pt-2 pb-2 w-full flex items-center justify-center md:justify-start flex-start gap-1 md:gap-4">
                <x-shared::metric-badge icon="visibility" size="md" :value="$viewModel->getReadsLoggedTotal()" :label="__('story::chapters.reads.label')"
                    :tooltip="__('story::chapters.reads.tooltip')" />

                <x-story::words-metric-badge size="md" :nb-words="$viewModel->getWordsTotal()" :nb-characters="$viewModel->getCharactersTotal()" />

                <x-read-list::read-list-counter-component :story-id="$viewModel->getId()" />
            </div>

            <!-- triggers, status, feedback... -->
            <div
                class="col-start-1 col-span-2 row-start-6 row-span-1 
                sm:col-start-2 sm:col-span-1 sm:row-start-4 sm:row-span-1
                flex flex-col justify-between gap-2 h-full">
                <!-- Trigger warnings -->
                <div class="flex flex-col gap-2">
                    <div class="font-semibold text-md leading-5">
                        @if ($viewModel->twDisclosure == 'no_tw')
                            <span class="material-symbols-outlined translate-y-1 text-success">warning</span>
                        @elseif ($viewModel->twDisclosure == 'unspoiled')
                            <span class="material-symbols-outlined translate-y-1 text-warning">warning</span>
                        @else
                            <span class="material-symbols-outlined translate-y-1 text-primary">warning</span>
                        @endif
                        <span>{{ __('story::show.trigger_warnings.label') }}</span>
                    </div>
                    @switch($viewModel->twDisclosure)
                        @case('no_tw')
                            <x-shared::popover placement="top">
                                <x-slot name="trigger">
                                    <x-shared::badge color="success" size="md"
                                        title="{{ __('story::shared.trigger_warnings.label') }}">
                                        {{ __('story::shared.trigger_warnings.no_tw') }}
                                    </x-shared::badge>
                                </x-slot>
                                <div>{{ __('story::shared.trigger_warnings.tooltips.no_tw') }}</div>
                            </x-shared::popover>
                        @break

                        @case('unspoiled')
                            <x-shared::popover placement="top">
                                <x-slot name="trigger">
                                    <x-shared::badge color="warning" size="md"
                                        title="{{ __('story::shared.trigger_warnings.label') }}">
                                        {{ __('story::shared.trigger_warnings.unspoiled') }}
                                    </x-shared::badge>
                                </x-slot>
                                <div>{{ __('story::shared.trigger_warnings.tooltips.unspoiled') }}</div>
                            </x-shared::popover>
                        @break

                        @default
                            @php($tws = $viewModel->getTriggerWarningRefs())
                            @if (!empty($tws))
                                <span class="inline-flex flex-wrap gap-2">
                                    @foreach ($tws as $tw)
                                        <x-story::ref-badge :name="$tw->getName()" :description="$tw->getDescription()" color="primary"
                                            size="md" />
                                    @endforeach
                                </span>
                            @endif
                    @endswitch
                </div>

                <!-- Copyright badge -->
                <div class="text-sm text-gray-700">
                    <span class="inline-flex flex-wrap gap-2">
                        @if ($viewModel->getCopyrightName())
                            <x-story::ref-badge name="{{ $viewModel->getCopyrightName() }}"
                                description="{{ $viewModel->getCopyrightDescription() }}" color="neutral"
                                :outline="true" size="sm" icon="copyright" />
                        @endif
                    </span>
                </div>
            </div>

            <!-- Summary -->
            <div
                class="col-span-2 flex flex-col flex-start h-full gap-2
                lg:col-start-3 lg:col-span-1 lg:row-span-3 lg:row-start-2">
                <div class="p-4 bg-bg flex-1">
                    <article
                        class="min-w-0 w-full p-4 prose flex-1 [text-indent:2rem] surface-read text-on-surface overflow-x-hidden [overflow-wrap:anywhere] h-full">
                        @if (!$viewModel->hasDescription())
                            <p class="italic text-gray-500">{{ __('story::show.no_description') }}</p>
                        @else
                            {!! $viewModel->getDescription() !!}
                        @endif
                    </article>
                </div>

            </div>

            <div
                class="col-span-2 flex justify-end h-full gap-2 items-center
            lg:col-start-3 lg:col-span-1 lg:row-span-1 lg:row-start-5
            ">
                @if ($viewModel->story->last_chapter_published_at)
                    <div x-data="{ date: new Date('{{ $viewModel->story->last_chapter_published_at }}') }">
                        {{ __('story::show.last_update') }} <span x-text="DateUtils.formatDate(date)"></span>

                    </div>
                @endif
                @if (!$viewModel->isAuthor())
                    <div class="flex gap-2">
                        <x-moderation::report-button topic-key="story" size="sm" :compact="true"
                            :entity-id="$viewModel->getId()" />
                        @if ($isModerator)
                            <x-moderation::moderation-button badgeColor="warning" position="top"
                                id="story-moderator-btn">
                                <x-moderation::action :action="route('stories.moderation.make-private', $viewModel->getSlug())" method="POST" :label="__('story::moderation.make_private.label')" />
                                <x-moderation::action :action="route('stories.moderation.empty-summary', $viewModel->getSlug())" method="POST" :label="__('story::moderation.empty_summary.label')" />
                            </x-moderation::moderation-button>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
    {{-- Chapters section --}}
    <div class="w-full mb-4">
        @if ($viewModel->isAuthor())
            @include('story::chapters.partials.chapter-list.author-view', [
                'story' => $viewModel->story,
                'chapters' => $viewModel->chapters,
                'creditsExhausted' => $creditsExhausted ?? false,
            ])
        @else
            @include('story::chapters.partials.chapter-list.reader-view', [
                'story' => $viewModel->story,
                'chapters' => $viewModel->chapters,
            ])
        @endif
    </div>

    @if ($viewModel->isAuthor())
        <x-shared::confirm-modal name="confirm-delete-story" :title="__('story::show.delete_confirm_title')" :body="__('story::show.delete_confirm_body')" :cancel="__('story::show.cancel')"
            :confirm="__('story::show.confirm_delete')" :action="route('stories.destroy', ['slug' => $viewModel->getSlug()])" method="DELETE" maxWidth="md" />
    @endif
</x-app-layout>
