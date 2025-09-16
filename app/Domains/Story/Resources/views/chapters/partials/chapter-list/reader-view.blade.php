<section class="mt-10">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">{{ __('story::chapters.sections.chapters') }}</h2>
    </div>

    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @include('story::chapters.partials.chapter-list.reader-list', ['story' => $story, 'chapters' => $chapters])
</section>
