@php
    $groups = $summary['groups'];
    $pending = $summary['pending'];
    $modes = $summary['modes'];
@endphp
<div class="flex flex-col gap-4">
    @if ($modes)
        <div class="text-sm flex flex-col gap-1" role="note">
            <span class="font-medium">{{ __('fanart::ui.tab.mode_title') }}</span>
            <span>{{ __('fanart::ui.tab.mode_images_' . $modes['images']) }}</span>
            <span>{{ __('fanart::ui.tab.mode_audio_' . $modes['audio']) }}</span>
            @if ($modes['coauthors'])
                <span>{{ __('fanart::ui.tab.mode_coauthors') }}</span>
            @endif
        </div>
    @endif

    @if ($pending->isNotEmpty())
        <section class="surface-read text-on-surface p-4 flex flex-col gap-3" data-pending-block>
            <x-shared::title tag="h3">{{ __('fanart::ui.tab.pending_title') }}</x-shared::title>
            <div class="flex flex-wrap gap-4">
                @foreach ($pending as $item)
                    <x-fanart::thumbnail :fanart="$item" />
                @endforeach
            </div>
        </section>
    @endif

    @forelse ($groups as $group)
        @php($url = route('fanart.group', [$story->slug, $group['key']]))
        <section class="surface-read text-on-surface p-4 flex flex-col gap-3"
                 data-group-key="{{ $group['key'] }}" data-collapsed="{{ $group['collapsed'] ? 'true' : 'false' }}"
                 data-group-url="{{ $url }}"
                 x-data="{
                     open: {{ $group['collapsed'] ? 'false' : 'true' }},
                     loaded: false,
                     html: '',
                     async load() {
                         if (this.loaded) return;
                         this.loaded = true;
                         this.html = (await window.fetchFragment(this.$root.dataset.groupUrl)) ?? '';
                     },
                     async toggle() {
                         this.open = !this.open;
                         if (this.open) await this.load();
                     },
                 }"
                 x-init="$nextTick(() => { if (open) load() })">
            <div class="flex items-center justify-between gap-2">
                <x-shared::title tag="h3">{{ $group['title'] ?? __('fanart::ui.tab.whole_work') }}</x-shared::title>
                <button type="button" class="text-sm font-medium hover:text-accent" x-on:click="toggle()" :aria-expanded="open">
                    <span x-show="!open">{{ __('fanart::ui.tab.show_all') }}</span>
                    <span x-show="open" x-cloak>{{ __('fanart::ui.tab.hide_all') }}</span>
                </button>
            </div>
            @if ($group['collapsed'] && $group['key'] !== 'work')
                <p class="text-sm italic" x-show="!open">{{ __('fanart::ui.tab.spoiler') }}</p>
            @endif
            <div x-show="open" x-cloak x-html="html">
                {{ __('fanart::ui.tab.loading') }}
            </div>
        </section>
    @empty
        @if ($pending->isEmpty())
            <p class="text-center p-4">{{ __('fanart::ui.tab.empty') }}</p>
        @endif
    @endforelse
</div>
