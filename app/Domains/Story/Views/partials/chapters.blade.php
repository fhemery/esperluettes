<section class="mt-10">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">{{ __('story::chapters.sections.chapters') }}</h2>
        @if($isAuthor)
            <a href="{{ route('chapters.create', ['storySlug' => $story->slug]) }}"
               class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                {{ __('story::chapters.sections.add_chapter') }}
            </a>
        @endif
    </div>
    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @if (empty($chapters))
        <p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
    @else
        <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white">
            @foreach($chapters as $ch)
                <li class="p-3 flex items-center justify-between">
                    <a href="{{ $ch->url }}" class="text-indigo-700 hover:text-indigo-900 font-medium">
                        {{ $ch->title }}
                    </a>
                    @if($isAuthor && $ch->isDraft)
                        <span class="ml-3 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300" aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
