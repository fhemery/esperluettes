<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('faq.admin.faq-categories.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ $faqCategory->name }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('faq.admin.faq-categories.update', $faqCategory) }}" class="surface-bg p-6 rounded-lg">
            @csrf
            @method('PUT')
            @include('faq::pages.admin.faq-categories._form', ['faqCategory' => $faqCategory])
        </form>
    </div>
</x-admin::layout>
