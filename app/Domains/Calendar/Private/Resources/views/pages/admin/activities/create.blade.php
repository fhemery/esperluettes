<x-admin::layout>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush

    <div class="flex flex-col gap-6 max-w-3xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('calendar.admin.activities.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('calendar::admin.activities.create_button') }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('calendar.admin.activities.store') }}"
              class="flex flex-col gap-6"
              enctype="multipart/form-data">
            @csrf
            @include('calendar::pages.admin.activities._form')
        </form>
    </div>
</x-admin::layout>
