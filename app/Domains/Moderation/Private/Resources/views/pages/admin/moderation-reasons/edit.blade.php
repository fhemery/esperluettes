<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('moderation.admin.moderation-reasons.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('moderation::admin.reasons.edit_title') }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('moderation.admin.moderation-reasons.update', $reason) }}"
              class="surface-read p-6">
            @csrf
            @method('PUT')
            @include('moderation::pages.admin.moderation-reasons._form', ['reason' => $reason, 'topics' => $topics])
        </form>
    </div>
</x-admin::layout>
