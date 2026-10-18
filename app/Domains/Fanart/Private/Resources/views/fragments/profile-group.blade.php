@php($pending = \App\Domains\Fanart\Private\Support\FanartStatus::Pending)
<div class="flex flex-col gap-3">
    @foreach ($groups as $group)
        <section class="flex flex-col gap-2"
                 data-group-key="{{ $group['key'] }}" data-collapsed="{{ $group['collapsed'] ? 'true' : 'false' }}"
                 x-data="{ open: {{ $group['collapsed'] ? 'false' : 'true' }} }">
            <div class="flex items-center justify-between gap-2">
                <x-shared::title tag="h3">{{ $group['title'] ?? __('fanart::ui.tab.whole_work') }}</x-shared::title>
                <button type="button" class="text-sm font-medium hover:text-accent" x-on:click="open = !open" :aria-expanded="open">
                    <span x-show="!open">{{ __('fanart::ui.tab.show_all') }}</span>
                    <span x-show="open" x-cloak>{{ __('fanart::ui.tab.hide_all') }}</span>
                </button>
            </div>
            @if ($group['collapsed'])
                <p class="text-sm italic" x-show="!open">{{ __('fanart::ui.tab.spoiler') }}</p>
            @endif
            <div x-show="open" x-cloak class="flex flex-wrap gap-4">
                @foreach ($group['fanarts'] as $fanart)
                    <div class="flex flex-col gap-1 items-start">
                        @if ($kind === \App\Domains\Fanart\Private\Support\FanartKind::Audio)
                            <button type="button" class="text-left font-medium hover:text-accent"
                                    x-on:click="$dispatch('fanart-open', { url: '{{ route('fanart.show', $fanart->id) }}' })">
                                {{ $fanart->title }}
                            </button>
                            <audio controls preload="none" src="{{ route('fanart.file', $fanart->id) }}" class="w-full max-w-md"
                                   aria-label="{{ $fanart->title }}"></audio>
                        @else
                            <x-fanart::thumbnail :fanart="$fanart" />
                        @endif

                        @if ($fanart->status === $pending)
                            <x-shared::badge color="warning">{{ __('fanart::ui.profile.pending_badge') }}</x-shared::badge>
                        @endif

                        @if ($isOwn)
                            <form action="{{ route('fanart.destroy', $fanart->id) }}" method="POST"
                                  onsubmit="return confirm(@js(__('fanart::ui.profile.delete_confirm')))">
                                @csrf
                                @method('DELETE')
                                <x-shared::button type="submit" color="error">{{ __('fanart::ui.viewer.delete') }}</x-shared::button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
