@props([
    'name', // input name, e.g., "genres[]"
    'options' => [], // array items: {slug,name} or any with valueField + name/label/text
    'selected' => [], // array of selected values (strings)
    'placeholderKey' => 'shared::components.searchable_multi_select.selected_count', // trans_choice key
    'emptyText' => 'No results',
    'maxHeight' => '15rem', // dropdown max height
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
            // If user passed single value, use it for both
            return ['slug' => (string) $o, 'name' => (string) $o];
        })
        ->values();
    $sel = collect($selected)->map(fn($v) => (string) $v)->filter()->values();

    // Precompute pluralization samples using trans_choice
    // We keep :count token in the many form to replace client-side with the live count
    $phSamples = [
        'zero' => trans_choice($placeholderKey, 0, ['count' => ':count']),
        'one' => trans_choice($placeholderKey, 1, ['count' => ':count']),
        'many' => trans_choice($placeholderKey, 2, ['count' => ':count']),
    ];
@endphp

<div x-data="searchableMultiSelect({
        name: @js($name),
        options: @js($opts),
        selected: @js($sel),
        emptyText: @js($emptyText),
        maxHeight: @js($maxHeight),
        placeholderSamples: @js($phSamples),
        color: @js($color),
    })" class="max-w-full" @click.outside="open = false" x-init="initiated = true">
    <div class="relative">
        <!-- Input only (no badges) -->
        <div class="min-h-10 w-full rounded-md border border-{{$color}} px-2 py-1.5 focus-within:ring-2 focus-within:ring-{{$color}}/90">
            <div class="flex items-center gap-1.5">
                <input type="text" x-model="state.query" @focus="open = true" @keydown.down.prevent="move(1)"
                       @keydown.up.prevent="move(-1)" @keydown.enter.prevent="chooseHighlighted()"
                       class="flex-1 min-w-[8rem] bg-transparent border-0 focus:ring-0 text-sm placeholder-accent"
                       :placeholder="state.query.length ? '' : countPlaceholder()">
            </div>
        </div>

        <!-- Dropdown -->
        <div x-cloak x-show="open" class="absolute z-20 mt-1 w-full rounded-md bg-white border border-{{$color}} ring-5 ring-{{$color}}">
            <ul class="max-h-60 overflow-auto py-1" :style="{maxHeight: maxHeight}">
                <template x-for="(opt, idx) in state.filtered" :key="opt.slug">
                    <li>
                        <button type="button" @mousedown.prevent="toggle(opt.slug)" @mouseenter="highlight = idx"
                                :class="itemClass(idx, opt.slug)" class="w-full text-left px-3 py-2 text-sm flex items-center gap-2">
                            <!-- Checkmark square -->
                            <span class="inline-block h-3.5 w-3.5 rounded-[2px] border border-{{$color}}"
                                  :class="state.selected.includes(opt.slug) ? 'bg-{{$color}}' : 'bg-transparent'"></span>
                            <span x-text="opt.name" class="flex-1"></span>
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
            if (!window.searchableMultiSelect) {
                window.searchableMultiSelect = function ({name, options, selected, emptyText, maxHeight, placeholderSamples, color}) {
                    return {
                        open: false,
                        emptyText: emptyText,
                        maxHeight: maxHeight,
                        highlight: -1,
                        initiated: false,
                        state: {
                            query: '',
                            options: options,
                            selected: selected ?? [],
                            get filtered() {
                                const q = this.query.trim().toLowerCase();
                                const sel = new Set(this.selected);
                                if (q) {
                                    const matches = this.options.filter(o => o.name.toLowerCase().includes(q));
                                    const checked = matches.filter(o => sel.has(o.slug));
                                    const unchecked = matches.filter(o => !sel.has(o.slug));
                                    return [...checked, ...unchecked];
                                }
                                // No query: show all, checked first
                                const checkedAll = this.options.filter(o => sel.has(o.slug));
                                const uncheckedAll = this.options.filter(o => !sel.has(o.slug));
                                return [...checkedAll, ...uncheckedAll];
                            }
                        },
                        itemClass(idx, slug) {
                            const isHighlighted = this.highlight === idx;
                            const isSelected = this.state.selected.includes(slug);
                            const base = isHighlighted ? 'bg-black/10 ' : '';
                            return base + (isSelected ? 'font-semibold text-' + color : 'text-' + color);
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
                                this.state.query = '';
                            }
                        },
                        countPlaceholder() {
                            const n = this.state.selected.length;
                            const sample = (n === 0) ? placeholderSamples.zero : (n === 1 ? placeholderSamples.one : placeholderSamples.many);
                            return sample.replace(':count', n);
                        },
                    }
                }
            }
        </script>
    @endpush
@endonce
