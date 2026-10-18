@if (! $visible)
    {{ $slot }}
@else
    @php($tabUrl = route('fanart.tab', $storySlug))
    {{-- `?tab=fanarts&fanart={id}` (report content URL) opens the Fanarts tab and the viewer. --}}
    <div data-fanart-tabs
         x-data="{
             tab: 'chapters',
             loaded: false,
             html: '',
             async loadFanarts() {
                 if (this.loaded) return;
                 this.loaded = true;
                 const response = await fetch(@js($tabUrl), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                 this.html = response.ok ? await response.text() : '';
             },
             select(name) {
                 this.tab = name;
                 if (name === 'fanarts') this.loadFanarts();
             },
         }"
         x-init="
             const params = new URLSearchParams(window.location.search);
             if (params.get('tab') === 'fanarts') {
                 select('fanarts');
                 const id = parseInt(params.get('fanart'), 10);
                 if (id > 0) $nextTick(() => $dispatch('fanart-open', { url: @js(url('/fanarts')) + '/' + id }));
             }
         ">
        <div class="flex gap-2 mb-4 border-b border-border" role="tablist" aria-label="{{ __('fanart::ui.story_page.tabs_label') }}"
             x-on:keydown.arrow-right.prevent="select(tab === 'chapters' ? 'fanarts' : 'chapters'); $nextTick(() => $refs[tab].focus())"
             x-on:keydown.arrow-left.prevent="select(tab === 'chapters' ? 'fanarts' : 'chapters'); $nextTick(() => $refs[tab].focus())"
             x-on:keydown.home.prevent="select('chapters'); $nextTick(() => $refs.chapters.focus())"
             x-on:keydown.end.prevent="select('fanarts'); $nextTick(() => $refs.fanarts.focus())">
            <button type="button" role="tab" id="fanart-tab-chapters" x-ref="chapters"
                    class="px-4 py-2 font-medium border-b-2"
                    :class="tab === 'chapters' ? 'border-accent text-accent' : 'border-transparent hover:text-accent'"
                    :aria-selected="(tab === 'chapters').toString()" :tabindex="tab === 'chapters' ? 0 : -1"
                    aria-controls="fanart-panel-chapters"
                    x-on:click="select('chapters')">{{ __('fanart::ui.story_page.tab_chapters') }}</button>
            <button type="button" role="tab" id="fanart-tab-fanarts" x-ref="fanarts"
                    class="px-4 py-2 font-medium border-b-2"
                    :class="tab === 'fanarts' ? 'border-accent text-accent' : 'border-transparent hover:text-accent'"
                    :aria-selected="(tab === 'fanarts').toString()" :tabindex="tab === 'fanarts' ? 0 : -1"
                    aria-controls="fanart-panel-fanarts"
                    x-on:click="select('fanarts')">{{ __('fanart::ui.story_page.tab_fanarts') }}</button>
        </div>

        <div id="fanart-panel-chapters" role="tabpanel" aria-labelledby="fanart-tab-chapters" x-show="tab === 'chapters'">
            {{ $slot }}
        </div>
        <div id="fanart-panel-fanarts" role="tabpanel" aria-labelledby="fanart-tab-fanarts" x-show="tab === 'fanarts'" x-cloak>
            <div x-html="html">{{ __('fanart::ui.tab.loading') }}</div>
        </div>
    </div>

    <x-fanart::viewer-modal />
@endif
