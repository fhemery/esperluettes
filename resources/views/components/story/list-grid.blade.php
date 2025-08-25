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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($viewModel->getItems() as $item)
            <div class="group block bg-white rounded shadow hover:shadow-md transition overflow-hidden">
                <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
                    <div class="w-[150px] h-[200px] mx-auto overflow-hidden">
                        <img
                            src="{{ asset('images/story/default-cover.svg') }}"
                            alt="{{ $item->getTitle() }}"
                            class="w-[150px] h-[200px] object-contain group-hover:scale-105 transition"
                        >
                    </div>
                    <div class="p-4 pt-2 pb-2">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="font-semibold text-gray-900 line-clamp-2">{{ $item->getTitle() }}</h2>
                            <x-shared::tooltip type="info" :title="__('story::shared.description.label')" placement="right">
                                {{ strip_tags($item->getDescription()) }}
                            </x-shared::tooltip>
                        </div>
                    </div>
                </a>
                @if($displayAuthors)
                    <div class="p-4 pt-0 text-sm text-gray-600">
                        <span class="font-medium">{{ __('story::shared.by') }} </span>
                        <x-profile.inline-names :profiles="$item->getAuthors()" />
                    </div>
                @endif
                @php($genres = $item->getGenreNames())
                @if(!empty($genres))
                    <div class="px-4 pb-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($genres as $g)
                                <span class="inline-block text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">{{ $g }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @php($tws = $item->getTriggerWarningNames())
                @if(!empty($tws))
                    <div class="px-4 pb-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($tws as $tw)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20">{{ $tw }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {!! $viewModel->links() !!}
    </div>
@endif
