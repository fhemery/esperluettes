<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }" 
    @reorder-cancel.window="reordering = false">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('story_ref::admin.feedbacks.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <a href="{{ route('story_ref.admin.feedbacks.export') }}" x-show="!reordering">
                    <x-shared::button color="neutral" :outline="true">
                        <span class="material-symbols-outlined text-[18px] leading-none">download</span>
                        {{ __('story_ref::admin.feedbacks.export_button') }}
                    </x-shared::button>
                </a>
                @if(count($feedbacks) > 1)
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
                <a href="{{ route('story_ref.admin.feedbacks.create') }}">
                    <x-shared::button color="primary" icon="add">
                        {{ __('story_ref::admin.feedbacks.create_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <!-- Reorderable list (shown when reordering) -->
        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table 
                :items="$feedbacks" 
                :reorderUrl="route('story_ref.admin.feedbacks.reorder')"
                eventName="reorder"
            />
        </div>

        <!-- Full details table (hidden when reordering) -->
        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story_ref::admin.feedbacks.table.order') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.feedbacks.table.name') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.feedbacks.table.slug') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.feedbacks.table.description') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.feedbacks.table.active') }}</th>
                        <th class="p-3">{{ __('story_ref::admin.feedbacks.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedbacks as $feedback)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $feedback->order }}</td>
                            <td class="p-3 font-medium">{{ $feedback->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $feedback->slug }}</td>
                            <td class="p-3 text-fg/70 max-w-xs truncate">{{ $feedback->description ?? '-' }}</td>
                            <td class="p-3">
                                @if ($feedback->is_active)
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success">
                                        {{ __('story_ref::admin.feedbacks.active_yes') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-fg/10 text-fg/50">
                                        {{ __('story_ref::admin.feedbacks.active_no') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('story_ref.admin.feedbacks.edit', $feedback) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('story_ref::admin.feedbacks.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    @php($confirm = str_replace("'", "\\'", __('story_ref::admin.feedbacks.confirm_delete')))
                                    <form method="POST" 
                                          action="{{ route('story_ref.admin.feedbacks.destroy', $feedback) }}" 
                                          onsubmit="return confirm('{{ $confirm }}')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-error hover:text-error/80"
                                                title="{{ __('story_ref::admin.feedbacks.delete_button') }}">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">
                                {{ __('story_ref::admin.feedbacks.no_feedbacks') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            </div>
</x-admin::layout>
