<x-admin::layout>
    <div class="flex flex-col gap-6"
         x-data="{ reordering: false }"
         @reorder-cancel.window="reordering = false">

        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('moderation::admin.reasons.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <x-shared::button color="secondary" icon="swap_vert" x-show="!reordering" x-cloak
                    x-on:click="reordering = true">
                    {{ __('moderation::admin.reasons.reorder_button') }}
                </x-shared::button>
                <a href="{{ route('moderation.admin.moderation-reasons.create') }}" x-show="!reordering" x-cloak>
                    <x-shared::button color="primary" icon="add">{{ __('moderation::admin.reasons.create_button') }}</x-shared::button>
                </a>
            </div>
        </div>

        <x-shared::flash-block />

        {{-- Filters --}}
        <div x-show="!reordering" x-cloak>
            <form method="GET" action="{{ route('moderation.admin.moderation-reasons.index') }}"
                  class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="filter_topic" class="block text-sm font-medium text-fg/70 mb-1">
                        {{ __('moderation::admin.reasons.table.topic') }}
                    </label>
                    <select id="filter_topic" name="topic_key"
                        class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
                        <option value="">{{ __('moderation::admin.reasons.filters.all_topics') }}</option>
                        @foreach ($topics as $key => $config)
                            <option value="{{ $key }}" @selected(request('topic_key') === $key)>
                                {{ $config['displayName'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_active" class="block text-sm font-medium text-fg/70 mb-1">
                        {{ __('moderation::admin.reasons.table.is_active') }}
                    </label>
                    <select id="filter_active" name="is_active"
                        class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
                        <option value="">{{ __('moderation::admin.reasons.filters.all_statuses') }}</option>
                        <option value="1" @selected(request('is_active') === '1')>{{ __('moderation::admin.reasons.filters.active_only') }}</option>
                        <option value="0" @selected(request('is_active') === '0')>{{ __('moderation::admin.reasons.filters.inactive_only') }}</option>
                    </select>
                </div>

                <x-shared::button type="submit" color="secondary" size="sm">
                    {{ __('moderation::admin.reasons.filters.apply') }}
                </x-shared::button>

                @if (request('topic_key') || request()->has('is_active'))
                    <a href="{{ route('moderation.admin.moderation-reasons.index') }}">
                        <x-shared::button type="button" color="neutral" size="sm">
                            {{ __('moderation::admin.reasons.filters.reset') }}
                        </x-shared::button>
                    </a>
                @endif
            </form>
        </div>

        {{-- Reorder view --}}
        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table
                :items="$reasons"
                :reorderUrl="route('moderation.admin.moderation-reasons.reorder')"
                nameField="label"
                eventName="reorder"
            />
        </div>

        {{-- Table view --}}
        <div x-show="!reordering" x-cloak class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('moderation::admin.reasons.table.sort_order') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reasons.table.topic') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reasons.table.label') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reasons.table.is_active') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reasons.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reasons as $reason)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 text-fg/60">{{ $reason->sort_order }}</td>
                            <td class="p-3">
                                @if (isset($topics[$reason->topic_key]))
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-primary/10 text-primary rounded">
                                        {{ $topics[$reason->topic_key]['displayName'] }}
                                    </span>
                                @else
                                    <span class="text-fg/40 text-xs">{{ $reason->topic_key }}</span>
                                @endif
                            </td>
                            <td class="p-3 font-medium">{{ $reason->label }}</td>
                            <td class="p-3">
                                <x-administration::active-badge :active="$reason->is_active" />
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('moderation.admin.moderation-reasons.edit', $reason) }}"
                                       class="text-primary hover:text-primary/80"
                                       title="{{ __('moderation::admin.reasons.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <x-administration::delete-button
                                        :action="route('moderation.admin.moderation-reasons.destroy', $reason)"
                                        :confirm="__('moderation::admin.reasons.confirm_delete')"
                                        :title="__('moderation::admin.reasons.delete_button')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-fg/50">
                                {{ __('moderation::admin.reasons.table.no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
