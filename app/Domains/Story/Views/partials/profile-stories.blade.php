<div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold text-gray-900">
        {{ $canEdit ? __('story::profile.my-stories') : __('story::profile.stories') }}
    </h2>

    @if($canCreateStory)
        <a href="{{ route('stories.create') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            {{ __('story::profile.new-story') }}
        </a>
    @endif
</div>

<x-story::list-grid :view-model="$viewModel" :display-authors="false"/>
