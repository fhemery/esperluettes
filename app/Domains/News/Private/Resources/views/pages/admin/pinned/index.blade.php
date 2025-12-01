<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('news::admin.pinned.title') }}</x-shared::title>
        </div>

        @if($pinnedNews->isEmpty())
            <div class="surface-read text-on-surface p-6 text-center">
                <span class="material-symbols-outlined text-[48px] text-fg/30 mb-2">push_pin</span>
                <p class="text-fg/60">{{ __('news::admin.pinned.empty') }}</p>
                <p class="text-sm text-fg/50 mt-2">{{ __('news::admin.pinned.empty_help') }}</p>
            </div>
        @else
            <p class="text-sm text-fg/60">{{ __('news::admin.pinned.help') }}</p>
            
            <x-administration::reorderable-table 
                :items="$pinnedNews" 
                :reorderUrl="route('news.admin.pinned.reorder')"
                nameField="title"
                eventName="pinned-reorder"
            />
        @endif
    </div>
</x-admin::layout>
