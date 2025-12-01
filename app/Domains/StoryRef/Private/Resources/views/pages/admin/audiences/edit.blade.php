<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('story_ref.admin.audiences.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('story_ref::admin.audiences.edit_title', ['name' => $audience->name]) }}</x-shared::title>
        </div>

        <form action="{{ route('story_ref.admin.audiences.update', $audience) }}" method="POST" class="surface-bg p-6 rounded-lg">
            @csrf
            @method('PUT')
            @include('story_ref::pages.admin.audiences._form', ['audience' => $audience])
        </form>
    </div>
</x-admin::layout>
