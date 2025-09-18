@props([
    'name', // input name, e.g., "genres[]"
    'options' => [], // array of [slug => string, name => string] or list of arrays with keys slug,name
    'selected' => [], // array of selected values (strings)
    'placeholder' => 'Search…',
    'emptyText' => 'No results',
    'maxHeight' => '15rem', // dropdown max height
    'badge' => 'accent', // badge color: indigo (default), blue, red
    'valueField' => 'slug', // which field from options to submit/match against (e.g., 'id' or 'slug')
    'color' => 'accent',
])

@php
    // Normalize options to a simple array of [slug, name]
    $opts = collect($options)
        ->map(function ($o) use ($valueField) {
            if (is_array($o)) {
                $val = $o[$valueField] ?? ($o['slug'] ?? ($o['value'] ?? ($o['id'] ?? '')));
                $label = $o['name'] ?? ($o['label'] ?? ($o['text'] ?? ''));
                return [
                    'slug' => (string) $val,
                    'name' => (string) $label,
                ];
            }
            // If user passed associative array slug => name
            return ['slug' => (string) $o, 'name' => (string) $o];
        })
        ->values();
    $sel = collect($selected)->map(fn($v) => (string) $v)->filter()->values();
    // Base name without [] (no longer used, we submit only array fields to avoid duplicates)
    $nameBase = preg_replace('/\[\]$/', '', (string) $name);
@endphp

<div x-data="searchMulti({
        name: @js($name),
        options: @js($opts),
        selected: @js($sel),
        placeholder: @js($placeholder),
        emptyText: @js($emptyText),
        maxHeight: @js($maxHeight),
        badge: @js($badge),
    })" class="w-[32rem] max-w-full" @click.outside="open = false" x-init="initiated = true">
    <div class="relative">
        <!-- Selected badges and input -->
        <div
            class="min-h-10 w-full rounded-md border border-{{$color}} px-2 py-1.5 focus-within:ring-2 focus-within:ring-accent/90">
            <div class="flex flex-wrap items-center gap-1.5">
                <!-- badges -->
                <template x-for="s in state.selectedDetailed" :key="s.slug">
                    <x-shared::badge :color="$badge" size="xs">
                        <span x-text="s.name"></span>
                        <button type="button" class="ml-0.5 surface-{{$badge}} text-on-surface"
                                @click="remove(s.slug)"
                                aria-label="Remove">
                            <span class="material-symbols-outlined text-[16px] leading-none text-on-surface">close</span>
                        </button>
                    </x-shared::badge>
                </template>

                <!-- text input -->
                <input type="text" x-model="state.query" @focus="open = true" @keydown.down.prevent="move(1)"
                       @keydown.up.prevent="move(-1)" @keydown.enter.prevent="chooseHighlighted()"
                       class="flex-1 min-w-[8rem] bg-transparent border-0 focus:ring-0 text-sm placeholder:text-{{$color}}/50"                      :placeholder="state.selected.length ? '' : placeholder">
            </div>
        </div>

        <!-- Dropdown -->
        <div x-cloak x-show="open" class="absolute z-20 mt-1 w-full rounded-md bg-white border border-{{$color}} ring-5 ring-{{$color}}">
            <ul class="max-h-60 overflow-auto py-1" :style="{maxHeight: maxHeight}">
                <template x-for="(opt, idx) in state.filtered" :key="opt.slug">
                    <li>
                        <button type="button" @mousedown.prevent="toggle(opt.slug)" @mouseenter="highlight = idx"
                                :class="itemClass(idx, opt.slug)" class="w-full text-left px-3 py-2 text-sm">
                            <span x-text="opt.name"></span>
                        </button>
                    </li>
                </template>
                <template x-if="!state.filtered.length">
                    <li class="px-3 py-2 text-sm text-gray-500" x-text="emptyText"></li>
                </template>
            </ul>
        </div>
    </div>

    <!-- Hidden inputs -->
    <!-- Server-rendered fallback (removed once Alpine initializes) -->
    <template x-if="!initiated">
        <div>
            @foreach($sel as $s)
                <input type="hidden" name="{{ $name }}" value="{{ $s }}">
            @endforeach
        </div>
    </template>
    <!-- Alpine-managed inputs after init -->
    <template x-if="initiated">
        <template x-for="slug in state.selected" :key="slug">
            <input type="hidden" name="{{ $name }}" :value="slug">
        </template>
    </template>

</div>

@once
    @push('scripts')
        <script>
    if (!window.searchMulti) {
        window.searchMulti = function ({name, options, selected, placeholder, emptyText, maxHeight, badge}) {
            return {
                open: false,
                placeholder: placeholder,
                emptyText: emptyText,
                maxHeight: maxHeight,
            highlight: -1,
            initiated: false,
            state: {
                query: '',
                options: options,
                selected: selected ?? [],
                get selectedDetailed() {
                    const s = new Set(this.selected);
                    return this.options.filter(o => s.has(o.slug));
                },
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    const sel = new Set(this.selected);
                    let list = this.options;
                    if (q) {
                        list = list.filter(o => o.name.toLowerCase().includes(q));
                    }
                    // show selected on top, then others
                    const selectedList = list.filter(o => sel.has(o.slug));
                    const others = list.filter(o => !sel.has(o.slug));
                    return [...selectedList, ...others];
                }
            },
            itemClass(idx, slug) {
                const isHighlighted = this.highlight === idx;
                const isSelected = this.state.selected.includes(slug);
                return (isHighlighted ? `bg-black/10 ` : '') + (isSelected ? `font-semibold text-${badge}` : `text-${badge}`);
            },
            move(delta) {
                const len = this.state.filtered.length;
                if (!len) return;
                this.highlight = (this.highlight + delta + len) % len;
            },
            chooseHighlighted() {
                if (this.highlight < 0) return;
                const item = this.state.filtered[this.highlight];
                if (item) this.toggle(item.slug);
            },
            toggle(slug) {
                const i = this.state.selected.indexOf(slug);
                if (i === -1) {
                    this.state.selected.push(slug);
                    this.state.query = '';
                } else {
                    this.state.selected.splice(i, 1);
                }
            },
            remove(slug) {
                this.state.selected = this.state.selected.filter(s => s !== slug);
            },
            }
        }
    }
        </script>
    @endpush
@endonce
