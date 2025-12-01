<x-admin::layout>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
    <div class="flex flex-col gap-6 max-w-4xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('news.admin.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('news::admin.news.create_title') }}</x-shared::title>
        </div>

        <form action="{{ route('news.admin.store') }}" method="POST" enctype="multipart/form-data" class="surface-bg p-6 rounded-lg">
            @csrf
            @include('news::pages.admin.news._form')
        </form>
    </div>
</x-admin::layout>
