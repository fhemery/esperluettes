{{-- The owner-facing visibility indicator is rendered by the profile page from the tab's ProfileTabPrivacy declaration. --}}
<div data-fanart-profile-tab class="flex flex-col gap-6">
    @foreach (['image' => 'images', 'audio' => 'sounds'] as $kind => $label)
        <section class="flex flex-col gap-3" data-profile-section="{{ $kind }}">
            <x-shared::title tag="h2">{{ __('fanart::ui.profile.section_' . $label) }}</x-shared::title>

            @forelse ($sections[$kind] as $story)
                @php($url = route('fanart.profile.group', [$ownerUserId, $story['storyId']]) . '?kind=' . $kind)
                <div class="flex flex-col gap-3" data-story-id="{{ $story['storyId'] }}" data-group-url="{{ $url }}"
                     x-data="{
                         open: false,
                         loaded: false,
                         html: '',
                         async toggle() {
                             this.open = !this.open;
                             if (!this.open || this.loaded) return;
                             this.loaded = true;
                             const response = await fetch(this.$root.dataset.groupUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                             this.html = response.ok ? await response.text() : '';
                         },
                     }">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('stories.show', $story['slug']) }}" class="font-medium hover:text-accent">
                            {{ $story['title'] }}
                        </a>
                        <button type="button" class="text-sm font-medium hover:text-accent" x-on:click="toggle()" :aria-expanded="open">
                            <span x-show="!open">{{ __('fanart::ui.profile.show_story', ['count' => $story['count']]) }}</span>
                            <span x-show="open" x-cloak>{{ __('fanart::ui.tab.hide_all') }}</span>
                        </button>
                    </div>
                    <div x-show="open" x-cloak x-html="html">{{ __('fanart::ui.tab.loading') }}</div>
                </div>
            @empty
                <p class="text-sm">{{ __('fanart::ui.profile.empty') }}</p>
            @endforelse
        </section>
    @endforeach

    <x-fanart::viewer-modal />
</div>
