<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ showDetails: false }">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('events::admin.domain_events.title') }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        {{-- Filters --}}
        <form method="GET" action="{{ route('events.admin.domain-events.index') }}"
              class="flex flex-wrap items-end gap-4">

            <div>
                <label for="filter_name" class="block text-sm font-medium text-fg/70 mb-1">
                    {{ __('events::admin.domain_events.filters.name_filter') }}
                </label>
                <input id="filter_name" name="name_filter" type="text"
                       value="{{ $nameFilter ?? '' }}"
                       class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
            </div>

            <div class="min-w-48">
                <label class="block text-sm font-medium text-fg/70 mb-1">
                    {{ __('events::admin.domain_events.filters.user_id') }}
                </label>
                <x-profile::user-search-input
                    name="user_id"
                    :value="$userId ? (int) $userId : null"
                    :initialDisplayName="$filterUserDisplayName ?? null"
                />
            </div>

            <div>
                <label for="filter_after" class="block text-sm font-medium text-fg/70 mb-1">
                    {{ __('events::admin.domain_events.filters.occurred_after') }}
                </label>
                <input id="filter_after" name="occurred_after" type="datetime-local"
                       value="{{ $occurredAfter ?? '' }}"
                       class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
            </div>

            <div>
                <label for="filter_before" class="block text-sm font-medium text-fg/70 mb-1">
                    {{ __('events::admin.domain_events.filters.occurred_before') }}
                </label>
                <input id="filter_before" name="occurred_before" type="datetime-local"
                       value="{{ $occurredBefore ?? '' }}"
                       class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
            </div>

            <x-shared::button type="submit" color="secondary" size="sm">
                {{ __('events::admin.domain_events.filters.apply') }}
            </x-shared::button>

            @if ($nameFilter || $userId || $occurredAfter || $occurredBefore)
                <a href="{{ route('events.admin.domain-events.index') }}">
                    <x-shared::button type="button" color="neutral" size="sm">
                        {{ __('events::admin.domain_events.filters.reset') }}
                    </x-shared::button>
                </a>
            @endif
        </form>

        {{-- Bulk delete form wrapping the table --}}
        <form method="POST" action="{{ route('events.admin.domain-events.bulk-destroy') }}"
              onsubmit="if (!document.querySelector('.bulk-cb:checked')) { return false; } return confirm('{{ __('events::admin.domain_events.actions.confirm_bulk', ['count' => '?']) }}')">
            @csrf

            {{-- Controls row: details toggle left, bulk delete right --}}
            <div class="flex items-center justify-between mb-3">
                <label class="flex items-center gap-2 text-sm text-fg/70 cursor-pointer">
                    <input type="checkbox" x-model="showDetails" class="rounded border-border">
                    {{ __('events::admin.domain_events.show_details') }}
                </label>
                <x-shared::button type="submit" color="danger" size="sm">
                    {{ __('events::admin.domain_events.actions.bulk_delete') }}
                </x-shared::button>
            </div>

            <div class="surface-read text-on-surface p-4 overflow-x-auto">
                <table class="w-full text-sm admin">
                    <thead>
                        <tr class="border-b border-border text-left">
                            <th class="p-3 w-8">
                                <input type="checkbox"
                                       class="rounded border-border"
                                       x-data
                                       @change="document.querySelectorAll('.bulk-cb').forEach(cb => cb.checked = $event.target.checked)">
                            </th>
                            <th class="p-3">{{ __('events::admin.domain_events.table.occurred_at') }}</th>
                            <th class="p-3">{{ __('events::admin.domain_events.table.event') }}</th>
                            <th class="p-3">{{ __('events::admin.domain_events.table.summary') }}</th>
                            <th class="p-3">{{ __('events::admin.domain_events.table.display_name') }}</th>
                            <th class="p-3">{{ __('events::admin.domain_events.table.user_id') }}</th>
                            <th x-show="showDetails" class="p-3 max-w-xs">{{ __('events::admin.domain_events.table.url') }}</th>
                            <th x-show="showDetails" class="p-3">{{ __('events::admin.domain_events.table.ip') }}</th>
                            <th x-show="showDetails" class="p-3 max-w-xs">{{ __('events::admin.domain_events.table.payload') }}</th>
                            <th class="p-3 text-right">{{ __('events::admin.domain_events.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr class="border-b border-border/50 hover:bg-surface-read/50">
                                <td class="p-3">
                                    <input type="checkbox" name="ids[]" value="{{ $event->id }}"
                                           class="bulk-cb rounded border-border">
                                </td>
                                <td class="p-3 text-fg/60 text-xs whitespace-nowrap">
                                    @if ($event->occurred_at)
                                        <time class="js-dt" datetime="{{ $event->occurred_at->toIso8601String() }}"></time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-xs break-all">
                                    {{ class_basename($event->name) }}
                                </td>
                                <td class="p-3 text-fg/70 text-xs">
                                    {{ $summaries[$event->id] ?? '—' }}
                                </td>
                                <td class="p-3 text-xs">
                                    {{ $profiles[$event->triggered_by_user_id]?->display_name ?? '—' }}
                                </td>
                                <td class="p-3 text-fg/60 text-xs">
                                    {{ $event->triggered_by_user_id ?? '—' }}
                                </td>
                                <td x-show="showDetails" class="p-3 text-fg/60 text-xs max-w-xs truncate">
                                    {{ $event->context_url ? Str::limit($event->context_url, 60) : '—' }}
                                </td>
                                <td x-show="showDetails" class="p-3 text-fg/60 text-xs">
                                    {{ $event->context_ip ?? '—' }}
                                </td>
                                <td x-show="showDetails" class="p-3 text-fg/60 text-xs max-w-xs truncate font-mono">
                                    @if ($event->payload)
                                        {{ Str::limit(json_encode($event->payload), 80) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('events.admin.domain-events.show', $event) }}"
                                       class="text-primary hover:text-primary/80"
                                       title="{{ __('events::admin.domain_events.actions.view') }}">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-6 text-center text-fg/50">
                                    {{ __('events::admin.domain_events.table.no_results') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        {{ $events->links() }}
    </div>
</x-admin::layout>
