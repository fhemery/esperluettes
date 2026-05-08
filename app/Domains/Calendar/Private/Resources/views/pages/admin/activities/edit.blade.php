<x-admin::layout>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush

    <div class="flex flex-col gap-6 max-w-3xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('calendar.admin.activities.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('calendar::admin.activities.edit_title') }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('calendar.admin.activities.update', $activity) }}"
              class="flex flex-col gap-6"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('calendar::pages.admin.activities._form', ['activity' => $activity])
        </form>
    </div>
</x-admin::layout>
