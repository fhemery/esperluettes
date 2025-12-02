<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.audiences.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <a href="{{ route('story_ref.admin.audiences.export') }}" x-show="!reordering">
                    <x-shared::button color="neutral" :outline="true">
                        <span class="material-symbols-outlined text-[18px] leading-none">download</span>
                        {{ __('story_ref::admin.audiences.export_button') }}
                    </x-shared::button>
                </a>
                @if(count($audiences) > 1)
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
                <a href="{{ route('story_ref.admin.audiences.create') }}">
                    <x-shared::button color="primary" icon="add">
                        {{ __('story_ref::admin.audiences.create_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <!-- Reorderable list (shown when reordering) -->
        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table 
                :items="$audiences" 
                :reorderUrl="route('story_ref.admin.audiences.reorder')"
                eventName="reorder"
            />
        </div>

        <!-- Full details table (hidden when reordering) -->
        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.order') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.name') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.slug') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.mature') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.threshold') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.active') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.audiences.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audiences as $audience)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $audience->order }}</td>
                            <td class="p-3 font-medium">{{ $audience->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $audience->slug }}</td>
                            <td class="p-3">
                                @if ($audience->is_mature_audience)
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-warning/20 text-warning">
                                        <span class="material-symbols-outlined text-sm mr-1">warning</span>
                                        {{ __('story_ref::admin.audiences.mature_yes') }}
                                    </span>
                                @else
                                    <span class="text-fg/50">-</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($audience->threshold_age)
                                    <span class="inline-flex items-center px-2 py-1 text-xs {{ $audience->is_mature_audience ? 'bg-error/20 text-error' : '' }}">
                                        {{ $audience->threshold_age }}
                                    </span>
                                @else
                                    <span class="text-fg/50">-</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <x-administration::active-badge :active="$audience->is_active" />
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.audiences.edit', $audience) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('story_ref::admin.audiences.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    
                                    <x-administration::delete-button 
                                        :action="route('story_ref.admin.audiences.destroy', $audience)"
                                        :confirm="__('story_ref::admin.audiences.confirm_delete')"
                                        :title="__('story_ref::admin.audiences.delete_button')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-fg/50">
                                {{ __('story_ref::admin.audiences.no_audiences') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            </div>
</x-admin::layout>
