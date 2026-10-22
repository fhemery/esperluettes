<section>
    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @include('story::chapters.partials.chapter-list.reader-list', ['story' => $story, 'chapters' => $chapters])
</section>
