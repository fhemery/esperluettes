<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('calendar::admin.activities.title') }}</x-shared::title>
            <a href="{{ route('calendar.admin.activities.create') }}">
                <x-shared::button color="primary">{{ __('calendar::admin.activities.create_button') }}</x-shared::button>
            </a>
        </div>

        <x-shared::flash-block />

        <div class="surface-read p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('calendar::admin.fields.name') }}</th>
                        <th class="p-3">{{ __('calendar::admin.fields.activity_type') }}</th>
                        <th class="p-3">{{ __('calendar::admin.fields.status') }}</th>
                        <th class="p-3">{{ __('calendar::admin.fields.preview_starts_at') }}</th>
                        <th class="p-3">{{ __('calendar::admin.fields.active_starts_at') }}</th>
                        <th class="p-3">{{ __('calendar::admin.fields.active_ends_at') }}</th>
                        <th class="p-3">{{ __('calendar::admin.fields.archived_at') }}</th>
                        <th class="p-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        @php
                            $state = (string) $activity->state;
                            $stateColors = [
                                'draft'    => 'bg-gray-100 text-gray-700',
                                'preview'  => 'bg-yellow-100 text-yellow-800',
                                'active'   => 'bg-green-100 text-green-800',
                                'ended'    => 'bg-orange-100 text-orange-700',
                                'archived' => 'bg-gray-200 text-gray-600',
                            ];
                            $stateColor = $stateColors[$state] ?? 'bg-gray-100 text-gray-700';
                            $typeLabel = __('calendar::activities.' . $activity->activity_type);
                            if ($typeLabel === 'calendar::activities.' . $activity->activity_type) {
                                $typeLabel = $activity->activity_type;
                            }
                        @endphp
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 font-medium">{{ $activity->name }}</td>
                            <td class="p-3 text-sm text-fg/70">{{ $typeLabel }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $stateColor }}">
                                    {{ __('calendar::admin.status.' . $state) }}
                                </span>
                            </td>
                            <td class="p-3 text-sm text-fg/70 whitespace-nowrap">
                                {{ $activity->preview_starts_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="p-3 text-sm text-fg/70 whitespace-nowrap">
                                {{ $activity->active_starts_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="p-3 text-sm text-fg/70 whitespace-nowrap">
                                {{ $activity->active_ends_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="p-3 text-sm text-fg/70 whitespace-nowrap">
                                {{ $activity->archived_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="p-3">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('calendar.admin.activities.edit', $activity) }}">
                                        <x-shared::button color="secondary" size="sm">{{ __('Modifier') }}</x-shared::button>
                                    </a>
                                    <x-administration::delete-button
                                        :action="route('calendar.admin.activities.destroy', $activity)"
                                        :confirm="__('calendar::admin.activities.confirm_delete')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-muted">{{ __('calendar::admin.activities.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $activities->links() }}
    </div>
</x-admin::layout>
