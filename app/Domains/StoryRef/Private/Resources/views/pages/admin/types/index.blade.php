<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.types.title') }}</x-shared::title>
            <x-story_ref::admin.index-actions
                :exportRoute="route('story_ref.admin.types.export')"
                :exportLabel="__('story_ref::admin.types.export_button')"
                :createRoute="route('story_ref.admin.types.create')"
                :createLabel="__('story_ref::admin.types.create_button')"
                :itemCount="count($types)"
            />
        </div>

        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table 
                :items="$types" 
                :reorderUrl="route('story_ref.admin.types.reorder')"
                eventName="reorder"
            />
        </div>

        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story_ref::admin.types.table.order') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.types.table.name') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.types.table.slug') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.types.table.active') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.types.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($types as $type)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $type->order }}</td>
                            <td class="p-3 font-medium">{{ $type->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $type->slug }}</td>
                            <td class="p-3">
                                <x-administration::active-badge :active="$type->is_active" />
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.types.edit', $type) }}" class="text-primary hover:text-primary/80" title="{{ __('story_ref::admin.types.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <x-administration::delete-button 
                                        :action="route('story_ref.admin.types.destroy', $type)"
                                        :confirm="__('story_ref::admin.types.confirm_delete')"
                                        :title="__('story_ref::admin.types.delete_button')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-fg/50">{{ __('story_ref::admin.types.no_types') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
