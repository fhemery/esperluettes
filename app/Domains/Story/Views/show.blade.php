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
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center">
                            <h1 class="font-semibold text-2xl text-gray-900 leading-tight mr-2">
                                {{ $viewModel->getTitle() }}
                            </h1>
                            @php
                            $label = match($viewModel->getVisibility()) {
                            'public' => __('story::shared.visibility.options.public'),
                            'community' => __('story::shared.visibility.options.community'),
                            'private' => __('story::shared.visibility.options.private'),
                            default => $viewModel->getVisibility(),
                            };
                            $badgeClasses = match($viewModel->getVisibility()) {
                            'public' => 'bg-green-100 text-green-800 ring-green-600/20',
                            'community' => 'bg-blue-100 text-blue-800 ring-blue-600/20',
                            'private' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
                            default => 'bg-gray-100 text-gray-800 ring-gray-600/20',
                            };
                            @endphp
                            <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">{{ $label }}</span>
                        </div>
                        @if($viewModel->isAuthor())
                        <a href="{{ url('/stories/'.$viewModel->getSlug().'/edit') }}"
                            class="text-indigo-600 hover:text-indigo-800"
                            aria-label="{{ __('story::show.edit') }}"
                            title="{{ __('story::show.edit') }}">
                            <span class="material-symbols-outlined">edit</span>
                        </a>
                        @endif
                    </div>

                    <div class="flex items-center justify-between mb-6 text-sm text-gray-600">
                        <div>
                            <span class="font-medium">{{ __('story::shared.by') }}
                            <x-profile.inline-names :profiles="$viewModel->authors" />
                            </span>
                        </div>
                        <div>
                            @php($date = app()->getLocale() === 'fr' ? $viewModel->getCreatedAt()->format('d-m-Y') : $viewModel->getCreatedAt()->format('Y-m-d'))
                            <span>{{ $date }}</span>
                        </div>
                    </div>

                    @if($viewModel->getTypeName())
                        <div class="mb-2 text-sm text-gray-700">
                            <span class="font-medium">{{ __('story::shared.type.label') }}:</span>
                            <span>{{ $viewModel->getTypeName() }}</span>
                        </div>
                    @endif

                    @if($viewModel->getAudienceName())
                        <div class="mb-4 text-sm text-gray-700">
                            <span class="font-medium">{{ __('story::shared.audience.label') }}:</span>
                            <span>{{ $viewModel->getAudienceName() }}</span>
                        </div>
                    @endif

                    @if($viewModel->getCopyrightName())
                        <div class="mb-4 text-sm text-gray-700">
                            <span class="font-medium">{{ __('story::shared.copyright.label') }}:</span>
                            <span>{{ $viewModel->getCopyrightName() }}</span>
                        </div>
                    @endif

                    @if($viewModel->getStatusName())
                        <div class="mb-4 text-sm text-gray-700">
                            <span class="font-medium">{{ __('story::shared.status.label') }}:</span>
                            <span>{{ $viewModel->getStatusName() }}</span>
                        </div>
                    @endif

                    @php($genres = $viewModel->getGenreNames())
                    @if(!empty($genres))
                        <div class="mb-6 text-sm text-gray-700">
                            <span class="font-medium">{{ __('story::shared.genres.label') }}:</span>
                            <span class="inline-flex flex-wrap gap-2 ml-2">
                                @foreach($genres as $g)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300">{{ $g }}</span>
                                @endforeach
                            </span>
                        </div>
                    @endif

                    @php($tws = $viewModel->getTriggerWarningNames())
                    @if(!empty($tws))
                        <div class="mb-6 text-sm text-gray-700">
                            <span class="font-medium">{{ __('story::shared.trigger_warnings.label') }}:</span>
                            <span class="inline-flex flex-wrap gap-2 ml-2">
                                @foreach($tws as $tw)
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20">{{ $tw }}</span>
                                @endforeach
                            </span>
                        </div>
                    @endif

                    <div class="flex gap-6 items-start">
                        <div class="shrink-0" aria-hidden="true">
                            <div class="w-[150px] h-[200px] rounded-lg border-4 border-[#ACE1AF] flex items-center justify-center bg-white">
                                <span class="text-[90px] leading-none font-serif text-[#ACE1AF] select-none">&amp;</span>
                            </div>
                        </div>
                        <article class="prose max-w-none">
                            <h2>{{ __('story::shared.description.label') }}</h2>
                            @if(!$viewModel->hasDescription())
                                <p class="italic text-gray-500">{{ __('story::show.no_description') }}</p>
                            @else
                                {!! $viewModel->getDescription() !!}
                            @endif
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
