<x-admin::layout>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush

    <div class="flex flex-col gap-6 max-w-3xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('faq.admin.faq-questions.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ $faqQuestion->question }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('faq.admin.faq-questions.update', $faqQuestion) }}"
              class="surface-bg p-6 rounded-lg"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('faq::pages.admin.faq-questions._form', [
                'faqQuestion' => $faqQuestion,
                'categories' => $categories,
            ])
        </form>
    </div>
</x-admin::layout>
