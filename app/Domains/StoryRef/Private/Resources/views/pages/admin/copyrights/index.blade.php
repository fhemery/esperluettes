<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ 
        reordering: false,
        deleteModalOpen: false,
        deleteUrl: '',
        deleteItemName: '',
        confirmDelete(url, name) {
            this.deleteUrl = url;
            this.deleteItemName = name;
            this.deleteModalOpen = true;
        }
    }" @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.copyrights.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <a href="{{ route('story_ref.admin.copyrights.export') }}" x-show="!reordering">
                    <x-shared::button color="neutral" :outline="true">
                        <span class="material-symbols-outlined text-[18px] leading-none">download</span>
                        {{ __('story_ref::admin.copyrights.export_button') }}
                    </x-shared::button>
                </a>
                @if(count($copyrights) > 1)
                    <x-shared::button 
                        color="neutral" 
                        :outline="true" 
                        x-show="!reordering"
                        x-on:click="reordering = true"
                    >
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('administration::reorder.button') }}
                    </x-shared::button>
                @endif
                <a href="{{ route('story_ref.admin.copyrights.create') }}">
                    <x-shared::button color="primary" icon="add">
                        {{ __('story_ref::admin.copyrights.create_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <!-- Reorderable list (shown when reordering) -->
        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table 
                :items="$copyrights" 
                :reorderUrl="route('story_ref.admin.copyrights.reorder')"
                eventName="reorder"
            />
        </div>

        <!-- Full details table (hidden when reordering) -->
        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story_ref::admin.copyrights.table.order') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.copyrights.table.name') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.copyrights.table.slug') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.copyrights.table.description') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.copyrights.table.active') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.copyrights.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($copyrights as $copyright)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $copyright->order }}</td>
                            <td class="p-3 font-medium">{{ $copyright->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $copyright->slug }}</td>
                            <td class="p-3 text-fg/70 max-w-xs truncate">{{ $copyright->description ?? '-' }}</td>
                            <td class="p-3">
                                @if ($copyright->is_active)
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success">
                                        {{ __('story_ref::admin.copyrights.active_yes') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-fg/10 text-fg/50">
                                        {{ __('story_ref::admin.copyrights.active_no') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.copyrights.edit', $copyright) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('story_ref::admin.copyrights.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <button type="button"
                                            class="text-error hover:text-error/80"
                                            title="{{ __('story_ref::admin.copyrights.delete_button') }}"
                                            @click="confirmDelete('{{ route('story_ref.admin.copyrights.destroy', $copyright) }}', '{{ addslashes($copyright->name) }}')">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">
                                {{ __('story_ref::admin.copyrights.no_copyrights') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="deleteModalOpen" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center"
             @keydown.escape.window="deleteModalOpen = false">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="deleteModalOpen = false"></div>
            <!-- Modal -->
            <div class="relative surface-bg rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <h3 class="text-lg font-semibold text-fg flex items-center gap-2">
                    <span class="material-symbols-outlined text-error">warning</span>
                    {{ __('story_ref::admin.copyrights.delete_modal.title') }}
                </h3>
                <p class="mt-3 text-fg/70">
                    {{ __('story_ref::admin.copyrights.delete_modal.message') }}
                    <strong x-text="deleteItemName"></strong> ?
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-shared::button type="button" color="neutral" @click="deleteModalOpen = false">
                        {{ __('story_ref::admin.copyrights.delete_modal.cancel') }}
                    </x-shared::button>
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <x-shared::button type="submit" color="error">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                            {{ __('story_ref::admin.copyrights.delete_modal.confirm') }}
                        </x-shared::button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin::layout>
