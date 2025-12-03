<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('story_ref.admin.genres.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('story_ref::admin.genres.create_title') }}</x-shared::title>
        </div>

        <form action="{{ route('story_ref.admin.genres.store') }}" method="POST" class="surface-bg p-6 rounded-lg">
            @csrf
            @include('story_ref::pages.admin.genres._form', ['nextOrder' => $nextOrder])
        </form>
    </div>
</x-admin::layout>
