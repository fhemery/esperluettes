@section('title', __("story::seo.index.title") . ' – ' . config('app.name'))
@push('meta')
    <meta name="description" content="{{ __("story::seo.index.description") }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __("story::seo.index.title") }}">
    <meta property="og:description" content="{{ __("story::seo.index.description") }}">
    <meta property="og:image" content="{{ asset('images/story/default-cover.svg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __("story::seo.index.title") }}">
    <meta name="twitter:description" content="{{ __("story::seo.index.description") }}">
    <meta name="twitter:image" content="{{ asset('images/story/default-cover.svg') }}">
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('story::index.heading') }}
        </h2>
    </x-slot>
    @php($hasFilters = !empty($currentType) || !empty($currentAudiences) || !empty($currentGenres) || !empty($currentExcludeTw))

    <!-- Filters Section -->
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-shared::collapsible title="{{__('story::shared.filters.header')}}" :open="$hasFilters">
                <form method="GET" action="{{ url('/stories') }}" class="flex items-start gap-6 flex-wrap" x-data
                      @submit="if($refs.type && $refs.type.value===''){ $refs.type.removeAttribute('name') }">
                    <div>
                        <label for="type"
                               class="block text-sm font-medium text-gray-700">{{ __('story::shared.type.label') }}</label>
                        <select id="type" name="type" x-ref="type"
                                class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— {{ __('story::shared.type.placeholder') }} —</option>
                            @foreach(($referentials['types'] ?? collect()) as $t)
                                <option
                                    value="{{ $t['slug'] }}" {{ (isset($currentType) && $currentType === $t['slug']) ? 'selected' : '' }}>{{ $t['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700">{{ __('story::shared.audience.label') }}</label>
                        @php($currentAud = collect($currentAudiences ?? []))
                        <div class="mt-2">
                            <x-search-multi
                                name="audiences[]"
                                :options="$referentials['audiences'] ?? []"
                                :selected="$currentAud"
                                :placeholder="__('story::shared.audience.placeholder')"
                                :empty-text="__('story::shared.no_results')"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700">{{ __('story::shared.genres.label') }}</label>
                        @php($currentGen = collect($currentGenres ?? []))
                        <div class="mt-2">
                            <x-search-multi
                                name="genres[]"
                                :options="$referentials['genres'] ?? []"
                                :selected="$currentGen"
                                :placeholder="__('story::shared.genres.placeholder')"
                                :empty-text="__('story::shared.no_results')"
                                badge="blue"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700">{{ __('story::shared.trigger_warnings.label') }}</label>
                        @php($currentExTw = collect($currentExcludeTw ?? []))
                        <div class="mt-2">
                            <x-search-multi
                                name="exclude_tw[]"
                                :options="$referentials['trigger_warnings'] ?? []"
                                :selected="$currentExTw"
                                :placeholder="__('story::shared.trigger_warnings.placeholder')"
                                :empty-text="__('story::shared.no_results')"
                                badge="red"
                            />
                        </div>
                    </div>

                    <div class="pt-6">
                        <x-primary-button type="submit">{{ __('story::index.filter') }}</x-primary-button>
                        @if($hasFilters)
                            <a href="{{ url('/stories') }}"
                               class="ml-3 text-sm text-gray-600 hover:text-gray-900">{{ __('story::index.reset_filters') }}</a>
                        @endif
                    </div>
                </form>
            </x-shared::collapsible>
        </div>
    </div>

    <!-- Results Section -->
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($viewModel->isEmpty())
                        <div class="text-center text-gray-600 py-16">
                            {{ __('story::index.empty') }}
                        </div>
                    @else
                        <x-story::list-grid :view-model="$viewModel"/>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
