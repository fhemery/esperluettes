<x-admin::layout>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
    <div class="flex flex-col gap-6 max-w-4xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('news.admin.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('news::admin.news.edit_title') }}</x-shared::title>
        </div>

        <form action="{{ route('news.admin.update', $news) }}" method="POST" enctype="multipart/form-data" class="surface-bg p-6 rounded-lg">
            @csrf
            @method('PUT')
            @include('news::pages.admin.news._form', ['news' => $news])
        </form>
    </div>
</x-admin::layout>
