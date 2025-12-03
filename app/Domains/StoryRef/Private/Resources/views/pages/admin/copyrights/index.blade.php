<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.copyrights.title') }}</x-shared::title>
            <x-story_ref::admin.index-actions
                :exportRoute="route('story_ref.admin.copyrights.export')"
                :exportLabel="__('story_ref::admin.copyrights.export_button')"
                :createRoute="route('story_ref.admin.copyrights.create')"
                :createLabel="__('story_ref::admin.copyrights.create_button')"
                :itemCount="count($copyrights)"
            />
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
                                <x-administration::active-badge :active="$copyright->is_active" />
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.copyrights.edit', $copyright) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('story_ref::admin.copyrights.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <x-administration::delete-button 
                                        :action="route('story_ref.admin.copyrights.destroy', $copyright)"
                                        :confirm="__('story_ref::admin.copyrights.confirm_delete')"
                                        :title="__('story_ref::admin.copyrights.delete_button')" />
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

            </div>
</x-admin::layout>
