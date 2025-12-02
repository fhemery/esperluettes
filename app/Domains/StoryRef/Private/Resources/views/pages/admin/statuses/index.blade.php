<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.statuses.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <a href="{{ route('story_ref.admin.statuses.export') }}" x-show="!reordering">
                    <x-shared::button color="neutral" :outline="true">
                        <span class="material-symbols-outlined text-[18px] leading-none">download</span>
                        {{ __('story_ref::admin.statuses.export_button') }}
                    </x-shared::button>
                </a>
                @if(count($statuses) > 1)
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
                <a href="{{ route('story_ref.admin.statuses.create') }}">
                    <x-shared::button color="primary" icon="add">
                        {{ __('story_ref::admin.statuses.create_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table 
                :items="$statuses" 
                :reorderUrl="route('story_ref.admin.statuses.reorder')"
                eventName="reorder"
            />
        </div>

        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story_ref::admin.statuses.table.order') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.statuses.table.name') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.statuses.table.slug') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.statuses.table.description') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.statuses.table.active') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.statuses.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statuses as $status)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $status->order }}</td>
                            <td class="p-3 font-medium">{{ $status->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $status->slug }}</td>
                            <td class="p-3 text-fg/70 max-w-xs truncate">{{ $status->description ?? '-' }}</td>
                            <td class="p-3">
                                @if ($status->is_active)
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success">{{ __('story_ref::admin.statuses.active_yes') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-fg/10 text-fg/50">{{ __('story_ref::admin.statuses.active_no') }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.statuses.edit', $status) }}" class="text-primary hover:text-primary/80" title="{{ __('story_ref::admin.statuses.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    @php($confirm = str_replace("'", "\\'", __('story_ref::admin.statuses.confirm_delete')))
                                    <form method="POST" action="{{ route('story_ref.admin.statuses.destroy', $status) }}" onsubmit="return confirm('{{ $confirm }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error hover:text-error/80" title="{{ __('story_ref::admin.statuses.delete_button') }}">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">{{ __('story_ref::admin.statuses.no_statuses') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
