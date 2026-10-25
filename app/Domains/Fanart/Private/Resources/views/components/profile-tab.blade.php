{{-- The owner-facing visibility indicator is rendered by the profile page from the tab's ProfileTabPrivacy declaration. --}}
<div data-fanart-profile-tab class="flex flex-col gap-4">
    @foreach (['image' => 'images', 'audio' => 'sounds'] as $kind => $label)
        @php($total = array_sum(array_column($sections[$kind], 'count')))
        <div data-profile-section="{{ $kind }}">
            <x-shared::collapsible color="secondary">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold">{{ __('fanart::ui.profile.section_' . $label) }}</span>
                        <span class="text-gray-500 text-sm">({{ trans_choice('fanart::ui.profile.count', $total, ['count' => $total]) }})</span>
                    </div>
                </x-slot:header>

                <div class="flex flex-col gap-2">
                    @forelse ($sections[$kind] as $story)
                        @php($url = route('fanart.profile.group', [$ownerUserId, $story['storyId']]) . '?kind=' . $kind)
                        <div class="border border-secondary" data-story-id="{{ $story['storyId'] }}" data-group-url="{{ $url }}"
                             x-data="{
                                 open: false,
                                 loaded: false,
                                 html: '',
                                 async toggle() {
                                     this.open = !this.open;
                                     if (!this.open || this.loaded) return;
                                     this.loaded = true;
                                     this.html = (await window.fetchFragment(this.$root.dataset.groupUrl)) ?? '';
                                 },
                             }">
                            <button type="button" class="w-full flex items-center justify-between gap-3 px-4 py-2 text-left bg-read"
                                    x-on:click="toggle()" :aria-expanded="open">
                                <span class="flex items-center gap-3">
                                    <a href="{{ route('stories.show', $story['slug']) }}" class="font-semibold hover:text-accent"
                                       x-on:click.stop>{{ $story['title'] }}</a>
                                    <span class="text-gray-500 text-sm">({{ trans_choice('fanart::ui.profile.count', $story['count'], ['count' => $story['count']]) }})</span>
                                </span>
                                <span class="material-symbols-outlined text-secondary transition-transform duration-200"
                                      :class="open ? 'rotate-180' : ''" aria-hidden="true">expand_less</span>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="border-t border-secondary p-4" x-html="html">
                                {{ __('fanart::ui.tab.loading') }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm">{{ __('fanart::ui.profile.empty') }}</p>
                    @endforelse
                </div>
            </x-shared::collapsible>
        </div>
    @endforeach

    <x-fanart::viewer-modal />
</div>
