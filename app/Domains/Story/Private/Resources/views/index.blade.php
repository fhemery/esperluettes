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
    @php($hasFilters = !empty($currentType) || !empty($currentAudiences) || !empty($currentGenres) || !empty($currentExcludeTw) || !empty($currentNoTwOnly))

    <div class="flex gap-2 text-4xl text-accent font-extrabold mb-4">
        <span class="material-symbols-outlined text-4xl">
            nest_eco_leaf
        </span>
        <h2>{{ __('story::index.title') }}</h2>
    </div>

    <!-- Filters Section -->
    <div class="w-full">
        <x-shared::collapsible :open="$hasFilters">
            <x-slot name="title">
                <span>{{__('story::shared.filters.header')}}</span>
            </x-slot>
            <form method="GET" action="{{ url('/stories') }}" class="flex items-start gap-6 flex-wrap" x-data
                @submit="if($refs.type && $refs.type.value===''){ $refs.type.removeAttribute('name') }">
                <div>
                    <label for="type"
                        class="flex gap-2 text-sm font-medium text-gray-700">{{ __('story::index.filters.type.label') }}</label>
                    <select id="type" name="type" x-ref="type"
                        class="mt-4 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('story::index.filters.type.placeholder') }}</option>
                        @foreach(($referentials['types'] ?? collect()) as $t)
                        <option
                            value="{{ $t['slug'] }}" {{ (isset($currentType) && $currentType === $t['slug']) ? 'selected' : '' }}>{{ $t['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="flex gap-2 text-sm font-medium text-gray-700">{{ __('story::index.filters.audiences.label') }}
                        <x-shared::tooltip type="help" placement="right">
                            {{ __('story::index.filters.audiences.help') }}
                        </x-shared::tooltip>
                    </label>
                    @php($currentAud = collect($currentAudiences ?? []))
                    <div class="mt-2">
                        <x-search-multi
                            name="audiences[]"
                            color="accent"
                            badge="fg"
                            :options="$referentials['audiences'] ?? []"
                            :selected="$currentAud"
                            :placeholder="__('story::shared.audience.placeholder')"
                            :empty-text="__('story::shared.no_results')" />
                    </div>
                </div>

                <div>
                    <label
                        class="flex gap-2 text-sm font-medium text-gray-700">{{ __('story::index.filters.genres.label') }}
                        <x-shared::tooltip type="help" placement="right">
                            {{ __('story::index.filters.genres.help') }}
                        </x-shared::tooltip>
                    </label>
                    @php($currentGen = collect($currentGenres ?? []))
                    <div class="mt-2">
                        <x-search-multi
                            name="genres[]"
                            :options="$referentials['genres'] ?? []"
                            :selected="$currentGen"
                            :placeholder="__('story::shared.genres.placeholder')"
                            :empty-text="__('story::shared.no_results')"
                            badge="accent"
                            color="accent" />
                    </div>
                </div>

                <div>
                    <label
                        class="flex gap-2 text-sm font-medium text-gray-700">
                        {{ __('story::index.filters.trigger_warnings.label') }}
                        <x-shared::tooltip type="help" placement="right">
                            {{ __('story::index.filters.trigger_warnings.help') }}                            
                        </x-shared::tooltip>
                    </label>
                    @php($currentExTw = collect($currentExcludeTw ?? []))
                    <div class="mt-2">
                        <x-shared::searchable-multi-select
                            name="exclude_tw[]"
                            :options="$referentials['trigger_warnings'] ?? []"
                            :selected="$currentExTw"
                            :empty-text="__('story::shared.no_results')"
                            color="accent" />
                    </div>
                    <div class="mt-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="no_tw_only" value="1" {{ !empty($currentNoTwOnly) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <span>{{ __('story::index.filters.no_tw_only.help') }}</span>
                        </label>
                    </div>
                </div>

                <div class="pt-6">
                    <x-shared::button color="accent" type="submit">{{ __('story::index.filter') }}</x-shared::button>
                    @if($hasFilters)
                    <a href="{{ url('/stories') }}"
                        class="ml-3 text-sm text-gray-600 hover:text-gray-900">{{ __('story::index.reset_filters') }}</a>
                    @endif
                </div>
            </form>
        </x-shared::collapsible>
    </div>

    <!-- Results Section -->
    <div class="w-full mt-4">
        <div class="overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                @if ($viewModel->isEmpty())
                <div class="text-center text-gray-600 py-16">
                    {{ __('story::index.empty') }}
                </div>
                @else
                <x-story::list-grid :view-model="$viewModel" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>