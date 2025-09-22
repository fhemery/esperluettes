@props([
    // StoryListViewModel
    'viewModel',
    'displayAuthors' => true,
])

@if ($viewModel->isEmpty())
    <div class="text-center text-gray-600 py-16">
        {{ __('story::index.empty') }}
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 justify-items-center gap-x-16 gap-y-8">
        @foreach ($viewModel->getItems() as $item)
            <x-story::card :item="$item" :display-authors="$displayAuthors" />
        @endforeach
    </div>

    <div class="mt-8">
        {!! $viewModel->links() !!}
    </div>
@endif

