<x-admin::layout>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
    <div class="flex flex-col gap-6 max-w-4xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('static.admin.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('static::admin.pages.create_title') }}</x-shared::title>
        </div>

        @include('static::pages.admin._form', [
            'page' => null,
            'action' => route('static.admin.store'),
            'method' => 'POST',
        ])
    </div>
</x-admin::layout>
