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

    {{-- TODO US-032: list chapters with status and actions --}}
    <p class="text-sm text-gray-600">{{ __('story::chapters.sections.chapters') }} — {{ __('story::shared.coming_soon') ?? 'À venir' }}</p>
</section>
