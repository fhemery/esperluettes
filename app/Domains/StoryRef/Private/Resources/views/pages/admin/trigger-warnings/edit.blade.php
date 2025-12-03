<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('story_ref.admin.trigger-warnings.index') }}" class="text-fg/60 hover:text-fg"><span class="material-symbols-outlined">arrow_back</span></a>
            <x-shared::title>{{ __('story_ref::admin.trigger_warnings.edit_title', ['name' => $triggerWarning->name]) }}</x-shared::title>
        </div>
        <form action="{{ route('story_ref.admin.trigger-warnings.update', $triggerWarning) }}" method="POST" class="surface-bg p-6 rounded-lg">
            @csrf
            @method('PUT')
            @include('story_ref::pages.admin.trigger-warnings._form', ['triggerWarning' => $triggerWarning])
        </form>
    </div>
</x-admin::layout>
