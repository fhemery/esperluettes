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
    
    <x-shared::title icon="nest_eco_leaf">{{ __('story::index.title') }}</x-shared::title>
    
    <!-- Filters Section -->
    <div class="w-full">
        <x-shared::collapsible :open="$hasFilters">
            <x-slot name="title">
                <span>{{__('story::shared.filters.header')}}</span>
            </x-slot>
            <form method="GET" action="{{ url('/stories') }}" x-data
                @submit="if($refs.type && $refs.type.value===''){ $refs.type.removeAttribute('name') }">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-6 items-start">
                    <!-- Genres -->
                    <div>
                        <label
                            class="flex gap-2 text-sm font-medium text-fg">{{ __('story::index.filters.genres.label') }}
                            <x-shared::tooltip type="help" placement="right">
                                {{ __('story::index.filters.genres.help') }}
                            </x-shared::tooltip>
                        </label>
                        @php($currentGen = collect($currentGenres ?? []))
                        <div class="mt-2">
                            <x-shared::searchable-multi-select
                                name="genres[]"
                                :options="$referentials['genres'] ?? []"
                                :selected="$currentGen"
                                :empty-text="__('story::shared.no_results')"
                                :placeholder="__('story::index.filters.genres.placeholder')"
                                color="accent" />
                        </div>
                    </div>

                    <!-- Type -->
                    <div class="flex flex-col gap-2">
                        <label for="type"
                            class="flex gap-2 text-sm font-medium text-fg">{{ __('story::index.filters.type.label') }}</label>
                        <x-shared::select-with-tooltips
                            name="type"
                            :options="$referentials['types'] ?? []"
                            :selected="$currentType ?? ''"
                            :placeholder="__('story::index.filters.type.placeholder')"
                            valueField="slug"
                            labelField="name"
                            descriptionField="description"
                            color="accent" />
                    </div>

                    <!-- Audiences -->
                    <div>
                        <label
                            class="flex gap-2 text-sm font-medium text-fg">{{ __('story::index.filters.audiences.label') }}
                            <x-shared::tooltip type="help" placement="right">
                                {{ __('story::index.filters.audiences.help') }}
                            </x-shared::tooltip>
                        </label>
                        @php($currentAud = collect($currentAudiences ?? []))
                        <div class="mt-2">
                            <x-shared::searchable-multi-select
                                name="audiences[]"
                                :options="$referentials['audiences'] ?? []"
                                :selected="$currentAud"
                                :empty-text="__('story::shared.no_results')"
                                :placeholder="__('story::index.filters.audiences.placeholder')"
                                color="accent" />
                        </div>
                    </div>

                    <!-- Trigger warnings -->
                    <div>
                        <label
                            class="flex gap-2 text-sm font-medium text-fg">
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
                                :placeholder="__('story::index.filters.trigger_warnings.placeholder')"
                                color="accent" />
                        </div>
                        <div class="mt-4">
                            <label class="inline-flex flex-wrap items-center gap-2 text-sm text-fg">
                                <input type="checkbox" name="no_tw_only" value="1" {{ !empty($currentNoTwOnly) ? 'checked' : '' }} class="rounded border-accent text-accent shadow-sm focus:border-accent focus:ring-accent/10" />
                                <span>{{ __('story::index.filters.no_tw_only.label') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Actions row spanning all columns -->
                    <div class="col-span-full flex gap-2 items-center justify-center">
                        <x-shared::button color="accent" type="submit">{{ __('story::index.filter') }}</x-shared::button>
                        <a href="{{ url('/stories') }}"
                            class="ml-3 text-sm text-gray-600 hover:text-gray-900">
                            <x-shared::button color="neutral" :outline="true" type="button">{{ __('story::index.reset_filters') }}</x-shared::button>
                        </a>
                    </div>
                </div>
            </form>
        </x-shared::collapsible>
    </div>

    <!-- Results Section -->
    <div class="w-full mt-4">
        <div class="overflow-hidden">
            <div class="text-fg">
                @if ($viewModel->isEmpty())
                <div class="text-center text-fg py-16">
                    {{ __('story::index.empty') }}
                </div>
                @else
                <x-story::list-grid :view-model="$viewModel" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>