<section class="mt-10">
    <div class="flex items-center justify-between mb-4">
        <x-shared::title tag="h2">{{ __('story::chapters.sections.chapters') }}</x-shared::title>
    </div>

    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @include('story::chapters.partials.chapter-list.reader-list', ['story' => $story, 'chapters' => $chapters])
</section>
