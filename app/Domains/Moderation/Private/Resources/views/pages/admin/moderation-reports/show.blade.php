<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>
                {{ __('moderation::admin.reports.show.title', ['id' => $moderationReport->id]) }}
            </x-shared::title>
            <div class="flex gap-2">
                @if (filled($moderationReport->content_url))
                    <a href="{{ $moderationReport->content_url }}" target="_blank" rel="noopener">
                        <x-shared::button color="secondary" size="sm" icon="open_in_new">
                            {{ __('moderation::admin.reports.actions.open') }}
                        </x-shared::button>
                    </a>
                @endif
                <a href="{{ route('moderation.admin.moderation-reports.index') }}">
                    <x-shared::button color="neutral" size="sm">
                        {{ __('moderation::admin.reports.show.back') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <x-shared::flash-block />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Report details --}}
            <div class="surface-read p-6 flex flex-col gap-4">
                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('moderation::admin.reports.show.topic') }}</div>
                    <span class="inline-flex items-center px-2 py-1 text-sm bg-primary/10 text-primary rounded">
                        {{ $topicLabel }}
                    </span>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('moderation::admin.reports.show.entity_id') }}</div>
                    <div class="text-fg font-mono">{{ $moderationReport->entity_id }}</div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('moderation::admin.reports.show.reason') }}</div>
                    <div class="text-fg">{{ $moderationReport->reason?->label ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('moderation::admin.reports.show.reporter') }}</div>
                    <div class="text-fg">
                        {{ $reporter?->display_name ?? __('moderation::admin.reports.show.anonymous') }}
                    </div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('moderation::admin.reports.show.status') }}</div>
                    @php
                        $badgeClass = match($moderationReport->status) {
                            'pending'   => 'bg-warning/15 text-warning',
                            'confirmed' => 'bg-success/15 text-success',
                            'dismissed' => 'bg-danger/15 text-danger',
                            default     => 'bg-fg/10 text-fg/60',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-1 text-sm rounded {{ $badgeClass }}">
                        {{ __('moderation::admin.reports.status.' . $moderationReport->status) }}
                    </span>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('moderation::admin.reports.show.created_at') }}</div>
                    <div class="text-fg/80 text-sm">{{ $moderationReport->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            {{-- Description + actions --}}
            <div class="flex flex-col gap-6">
                <div class="surface-read p-6">
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-2">{{ __('moderation::admin.reports.show.description') }}</div>
                    <div class="text-fg whitespace-pre-wrap">{{ $moderationReport->description ?: '—' }}</div>
                </div>

                {{-- Workflow actions --}}
                @if ($moderationReport->status === 'pending')
                    <div class="surface-read p-6 flex gap-3">
                        <form method="POST" action="{{ route('moderation.admin.moderation-reports.approve', $moderationReport) }}">
                            @csrf
                            <x-shared::button type="submit" color="primary">
                                {{ __('moderation::admin.reports.actions.approve') }}
                            </x-shared::button>
                        </form>

                        <form method="POST" action="{{ route('moderation.admin.moderation-reports.dismiss', $moderationReport) }}">
                            @csrf
                            <x-shared::button type="submit" color="secondary">
                                {{ __('moderation::admin.reports.actions.dismiss') }}
                            </x-shared::button>
                        </form>
                    </div>
                @endif

                {{-- Delete --}}
                <div class="surface-read p-6">
                    <form method="POST" action="{{ route('moderation.admin.moderation-reports.destroy', $moderationReport) }}"
                          onsubmit="return confirm('{{ __('moderation::admin.reports.actions.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <x-shared::button type="submit" color="danger" size="sm">
                            {{ __('moderation::admin.reports.actions.delete') }}
                        </x-shared::button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Snapshot --}}
        @if ($snapshot)
            <div class="surface-read p-6">
                <div class="text-xs text-fg/50 uppercase tracking-wide mb-4">{{ __('moderation::admin.reports.show.snapshot') }}</div>
                <div class="prose prose-sm max-w-none">{!! $snapshot !!}</div>
            </div>
        @endif

        {{-- Review comment --}}
        <div class="surface-read p-6">
            <form method="POST" action="{{ route('moderation.admin.moderation-reports.update-comment', $moderationReport) }}"
                  class="flex flex-col gap-4">
                @csrf @method('PUT')

                <x-shared::input-label for="review_comment">
                    {{ __('moderation::admin.reports.show.review_comment') }}
                </x-shared::input-label>
                <p class="text-xs text-fg/50 -mt-3">{{ __('moderation::admin.reports.show.review_comment_hint') }}</p>

                <textarea
                    id="review_comment"
                    name="review_comment"
                    rows="4"
                    class="w-full rounded-md border-border surface-read text-on-surface text-sm focus:border-primary focus:ring-primary"
                >{{ old('review_comment', $moderationReport->review_comment) }}</textarea>

                <x-shared::input-error :messages="$errors->get('review_comment')" />

                <div>
                    <x-shared::button type="submit" color="secondary" size="sm">
                        {{ __('moderation::admin.reports.show.save_comment') }}
                    </x-shared::button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layout>
