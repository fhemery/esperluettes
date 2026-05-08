<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>
                {{ __('events::admin.domain_events.show.title', ['id' => $domainEvent->id]) }}
            </x-shared::title>
            <a href="{{ route('events.admin.domain-events.index') }}">
                <x-shared::button color="neutral" size="sm">
                    {{ __('events::admin.domain_events.actions.back') }}
                </x-shared::button>
            </a>
        </div>

        <x-shared::flash-block />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Core fields --}}
            <div class="surface-read p-6 flex flex-col gap-4">
                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.id') }}</div>
                    <div class="text-fg font-mono">{{ $domainEvent->id }}</div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.occurred_at') }}</div>
                    <div class="text-fg text-sm">
                        @if ($domainEvent->occurred_at)
                            <time class="js-dt" datetime="{{ $domainEvent->occurred_at->toIso8601String() }}"></time>
                            <span class="text-fg/50 text-xs ml-1">({{ $domainEvent->occurred_at->format('Y/m/d H:i:s') }})</span>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.event') }}</div>
                    <div class="text-fg font-mono text-sm break-all">{{ $domainEvent->name }}</div>
                </div>

                @if ($summary)
                    <div>
                        <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.summary') }}</div>
                        <div class="text-fg text-sm">{{ $summary }}</div>
                    </div>
                @endif

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.display_name') }}</div>
                    <div class="text-fg text-sm">{{ $profile?->display_name ?? __('events::admin.domain_events.show.no_value') }}</div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.user_id') }}</div>
                    <div class="text-fg font-mono text-sm">{{ $domainEvent->triggered_by_user_id ?? __('events::admin.domain_events.show.no_value') }}</div>
                </div>
            </div>

            {{-- Context fields --}}
            <div class="surface-read p-6 flex flex-col gap-4">
                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.url') }}</div>
                    <div class="text-fg text-sm break-all">{{ $domainEvent->context_url ?? __('events::admin.domain_events.show.no_value') }}</div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.ip') }}</div>
                    <div class="text-fg font-mono text-sm">{{ $domainEvent->context_ip ?? __('events::admin.domain_events.show.no_value') }}</div>
                </div>

                <div>
                    <div class="text-xs text-fg/50 uppercase tracking-wide mb-1">{{ __('events::admin.domain_events.show.user_agent') }}</div>
                    <div class="text-fg text-sm break-all">{{ $domainEvent->context_user_agent ?? __('events::admin.domain_events.show.no_value') }}</div>
                </div>
            </div>
        </div>

        {{-- Payload --}}
        @if ($domainEvent->payload !== null)
            <div class="surface-read p-6" x-data="{ copied: false }">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs text-fg/50 uppercase tracking-wide">{{ __('events::admin.domain_events.show.payload') }}</div>
                    <button type="button"
                            @click="navigator.clipboard.writeText($refs.payload.textContent); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-xs text-fg/50 hover:text-fg flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">content_copy</span>
                        <span x-text="copied ? '{{ __('events::admin.domain_events.show.copied') }}' : '{{ __('events::admin.domain_events.show.copy') }}'"></span>
                    </button>
                </div>
                <pre x-ref="payload" class="font-mono text-xs bg-fg/5 p-4 rounded overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($domainEvent->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        {{-- Meta --}}
        @if ($domainEvent->meta !== null)
            <div class="surface-read p-6" x-data="{ copied: false }">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs text-fg/50 uppercase tracking-wide">{{ __('events::admin.domain_events.show.meta') }}</div>
                    <button type="button"
                            @click="navigator.clipboard.writeText($refs.meta.textContent); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-xs text-fg/50 hover:text-fg flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">content_copy</span>
                        <span x-text="copied ? '{{ __('events::admin.domain_events.show.copied') }}' : '{{ __('events::admin.domain_events.show.copy') }}'"></span>
                    </button>
                </div>
                <pre x-ref="meta" class="font-mono text-xs bg-fg/5 p-4 rounded overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($domainEvent->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        {{-- Delete --}}
        <div class="surface-read p-6">
            <form method="POST" action="{{ route('events.admin.domain-events.destroy', $domainEvent) }}"
                  onsubmit="return confirm('{{ __('events::admin.domain_events.actions.confirm') }}')">
                @csrf @method('DELETE')
                <x-shared::button type="submit" color="danger" size="sm">
                    {{ __('events::admin.domain_events.actions.delete') }}
                </x-shared::button>
            </form>
        </div>
    </div>
</x-admin::layout>
