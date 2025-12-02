<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.genres.title') }}</x-shared::title>
            <x-story_ref::admin.index-actions
                :exportRoute="route('story_ref.admin.genres.export')"
                :exportLabel="__('story_ref::admin.genres.export_button')"
                :createRoute="route('story_ref.admin.genres.create')"
                :createLabel="__('story_ref::admin.genres.create_button')"
                :itemCount="count($genres)"
            />
        </div>

        <!-- Reorderable list (shown when reordering) -->
        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table 
                :items="$genres" 
                :reorderUrl="route('story_ref.admin.genres.reorder')"
                eventName="reorder"
            />
        </div>

        <!-- Full details table (hidden when reordering) -->
        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story_ref::admin.genres.table.order') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.genres.table.name') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.genres.table.slug') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.genres.table.description') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.genres.table.active') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.genres.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($genres as $genre)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $genre->order }}</td>
                            <td class="p-3 font-medium">{{ $genre->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $genre->slug }}</td>
                            <td class="p-3 text-fg/70 max-w-xs truncate">{{ $genre->description ?? '-' }}</td>
                            <td class="p-3">
                                <x-administration::active-badge :active="$genre->is_active" />
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.genres.edit', $genre) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('story_ref::admin.genres.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <x-administration::delete-button 
                                        :action="route('story_ref.admin.genres.destroy', $genre)"
                                        :confirm="__('story_ref::admin.genres.confirm_delete')"
                                        :title="__('story_ref::admin.genres.delete_button')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">
                                {{ __('story_ref::admin.genres.no_genres') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
