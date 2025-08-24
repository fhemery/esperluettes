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
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <form method="GET" action="{{ url('/stories') }}" class="flex items-center gap-3">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">{{ __('story::shared.type.label') }}</label>
                                <select id="type" name="type" class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">— {{ __('story::shared.type.placeholder') }} —</option>
                                    @foreach(($types ?? collect()) as $t)
                                        <option value="{{ $t['slug'] }}" {{ (isset($currentType) && $currentType === $t['slug']) ? 'selected' : '' }}>{{ $t['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pt-6">
                                <x-primary-button type="submit">{{ __('story::index.filter') }}</x-primary-button>
                                @if(!empty($currentType))
                                    <a href="{{ url('/stories') }}" class="ml-3 text-sm text-gray-600 hover:text-gray-900">{{ __('story::index.reset_filters') }}</a>
                                @endif
                            </div>
                        </form>
                    </div>
                    @if ($viewModel->isEmpty())
                        <div class="text-center text-gray-600 py-16">
                            {{ __('story::index.empty') }}
                        </div>
                    @else
                        <x-story.list-grid :view-model="$viewModel" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
