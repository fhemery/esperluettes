<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('moderation::admin.reports.title') }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        {{-- Filters --}}
        <form method="GET" action="{{ route('moderation.admin.moderation-reports.index') }}"
              class="flex flex-wrap items-end gap-4">

            <div>
                <label for="filter_topic" class="block text-sm font-medium text-fg/70 mb-1">
                    {{ __('moderation::admin.reports.table.topic') }}
                </label>
                <select id="filter_topic" name="topic_key"
                    class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
                    <option value="">{{ __('moderation::admin.reports.filters.all_topics') }}</option>
                    @foreach ($distinctTopics as $key)
                        <option value="{{ $key }}" @selected($topicKey === $key)>
                            {{ isset($topics[$key]) ? $topics[$key]['displayName'] : $key }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_status" class="block text-sm font-medium text-fg/70 mb-1">
                    {{ __('moderation::admin.reports.table.status') }}
                </label>
                <select id="filter_status" name="status"
                    class="rounded-md border-border bg-surface text-fg text-sm focus:border-primary focus:ring-primary">
                    <option value="">{{ __('moderation::admin.reports.filters.all_statuses') }}</option>
                    <option value="pending" @selected($status === 'pending')>{{ __('moderation::admin.reports.status.pending') }}</option>
                    <option value="confirmed" @selected($status === 'confirmed')>{{ __('moderation::admin.reports.status.confirmed') }}</option>
                    <option value="dismissed" @selected($status === 'dismissed')>{{ __('moderation::admin.reports.status.dismissed') }}</option>
                </select>
            </div>

            <x-shared::button type="submit" color="secondary" size="sm">
                {{ __('moderation::admin.reports.filters.apply') }}
            </x-shared::button>

            @if ($topicKey || $status !== 'pending')
                <a href="{{ route('moderation.admin.moderation-reports.index') }}">
                    <x-shared::button type="button" color="neutral" size="sm">
                        {{ __('moderation::admin.reports.filters.reset') }}
                    </x-shared::button>
                </a>
            @endif
        </form>

        <div class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('moderation::admin.reports.table.id') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.topic') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.reason') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.description') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.reported_by') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.status') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.created_at') }}</th>
                        <th class="p-3">{{ __('moderation::admin.reports.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 text-fg/60">{{ $report->id }}</td>
                            <td class="p-3">
                                @if (isset($topics[$report->topic_key]))
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-primary/10 text-primary rounded">
                                        {{ $topics[$report->topic_key]['displayName'] }}
                                    </span>
                                @else
                                    <span class="text-fg/40 text-xs">{{ $report->topic_key }}</span>
                                @endif
                            </td>
                            <td class="p-3">{{ $report->reason?->label ?? '—' }}</td>
                            <td class="p-3 text-fg/80">
                                {{ Str::limit($report->description, 50) }}
                            </td>
                            <td class="p-3">
                                {{ $profiles[$report->reported_by_user_id]?->display_name ?? __('moderation::admin.reports.show.anonymous') }}
                            </td>
                            <td class="p-3">
                                @php
                                    $badgeClass = match($report->status) {
                                        'pending'   => 'bg-warning/15 text-warning',
                                        'confirmed' => 'bg-success/15 text-success',
                                        'dismissed' => 'bg-danger/15 text-danger',
                                        default     => 'bg-fg/10 text-fg/60',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded {{ $badgeClass }}">
                                    {{ __('moderation::admin.reports.status.' . $report->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-fg/60 text-xs">{{ $report->created_at->diffForHumans() }}</td>
                            <td class="p-3">
                                <div class="flex gap-2 items-center">
                                    <a href="{{ route('moderation.admin.moderation-reports.show', $report) }}"
                                       class="text-primary hover:text-primary/80"
                                       title="{{ __('moderation::admin.reports.actions.show') }}">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                    </a>

                                    @if (filled($report->content_url))
                                        <a href="{{ $report->content_url }}" target="_blank" rel="noopener"
                                           class="text-fg/60 hover:text-fg"
                                           title="{{ __('moderation::admin.reports.actions.open') }}">
                                            <span class="material-symbols-outlined text-base">open_in_new</span>
                                        </a>
                                    @endif

                                    @if ($report->status === 'pending')
                                        <form method="POST" action="{{ route('moderation.admin.moderation-reports.approve', $report) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="text-success hover:text-success/80"
                                                    title="{{ __('moderation::admin.reports.actions.approve') }}">
                                                <span class="material-symbols-outlined text-base">check</span>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('moderation.admin.moderation-reports.dismiss', $report) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="text-warning hover:text-warning/80"
                                                    title="{{ __('moderation::admin.reports.actions.dismiss') }}">
                                                <span class="material-symbols-outlined text-base">close</span>
                                            </button>
                                        </form>
                                    @endif

                                    <x-administration::delete-button
                                        :action="route('moderation.admin.moderation-reports.destroy', $report)"
                                        :confirm="__('moderation::admin.reports.actions.confirm_delete')"
                                        :title="__('moderation::admin.reports.actions.delete')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-fg/50">
                                {{ __('moderation::admin.reports.table.no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reports->links() }}
    </div>
</x-admin::layout>
