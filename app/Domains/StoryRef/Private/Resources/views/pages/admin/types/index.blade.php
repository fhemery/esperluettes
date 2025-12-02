<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.types.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <a href="{{ route('story_ref.admin.types.export') }}" x-show="!reordering">
                    <x-shared::button color="neutral" :outline="true">
                        <span class="material-symbols-outlined text-[18px] leading-none">download</span>
                        {{ __('story_ref::admin.types.export_button') }}
                    </x-shared::button>
                </a>
                @if(count($types) > 1)
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
                <a href="{{ route('story_ref.admin.types.create') }}">
                    <x-shared::button color="primary" icon="add">
                        {{ __('story_ref::admin.types.create_button') }}
                    </x-shared::button>
                </a>
            </div>
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
                                @if ($type->is_active)
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success">{{ __('story_ref::admin.types.active_yes') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-fg/10 text-fg/50">{{ __('story_ref::admin.types.active_no') }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.types.edit', $type) }}" class="text-primary hover:text-primary/80" title="{{ __('story_ref::admin.types.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    @php($confirm = str_replace("'", "\\'", __('story_ref::admin.types.confirm_delete')))
                                    <form method="POST" action="{{ route('story_ref.admin.types.destroy', $type) }}" onsubmit="return confirm('{{ $confirm }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error hover:text-error/80" title="{{ __('story_ref::admin.types.delete_button') }}">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
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
