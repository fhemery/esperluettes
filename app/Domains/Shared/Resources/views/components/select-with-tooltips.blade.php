{{--
    Select component with tooltips/descriptions for each option.
    Similar UI to searchable-multi-select but for single selection without search.
    
   
    Options format: [{id: '1', name: 'Option 1', description: 'Explanation...'}, ...]
--}}
@props([
    'name', // input name, e.g., "story_ref_copyright_id"
    'options' => [], // array items: {id, name, description}
    'selected' => '', // selected value (string)
    'placeholder' => 'Select an option',
    'emptyText' => 'No options available',
    'maxHeight' => '20rem',
    'valueField' => 'id',
    'labelField' => 'name',
    'descriptionField' => 'description',
    'required' => false,
    'color' => 'accent',
])

@php
    // Normalize options
    $opts = collect($options)
        ->map(function ($o) use ($valueField, $labelField, $descriptionField) {
            if (is_array($o)) {
                return [
                    'value' => (string) ($o[$valueField] ?? ($o['id'] ?? '')),
                    'label' => (string) ($o[$labelField] ?? ($o['name'] ?? '')),
                    'description' => (string) ($o[$descriptionField] ?? ($o['description'] ?? '')),
                ];
            }
            return ['value' => (string) $o, 'label' => (string) $o, 'description' => ''];
        })
        ->values();
    $selectedValue = (string) $selected;
@endphp

<div x-data="selectWithTooltips({
        name: @js($name),
        options: @js($opts),
        selected: @js($selectedValue),
        emptyText: @js($emptyText),
        maxHeight: @js($maxHeight),
        placeholder: @js($placeholder),
        color: @js($color),
    })"
    {{ $attributes->merge(['class' => 'relative w-full']) }}
    @click.outside="open = false"
    @keydown.escape.window="open = false">
    
    <!-- Display Button -->
    <button type="button" x-ref="button" @click="open = !open; if (open) { $nextTick(() => updatePosition()) }" 
            class="w-full flex items-center gap-2 justify-between text-left px-3 py-2 rounded-md border border-{{$color}} hover:border-{{$color}}/80 focus:outline-none focus:ring-2 focus:ring-{{$color}}/50 flex items-center justify-between">
        <span x-text="selectedLabel || @js($placeholder)" 
              :class="selectedLabel ? 'text-{{$color}} font-semibold' : 'text-{{$color}}/70'" class="flex-1 truncate"></span>
        <span class="material-symbols-outlined text-{{$color}} transition-transform" 
              :class="open ? 'rotate-180' : ''">expand_more</span>
    </button>

    <!-- Dropdown -->
    <template x-teleport="body">
    <div x-cloak x-show="open" @click.outside="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-ref="dropdown"
         :style="dropdownStyle"
         class="fixed z-[100] mt-1 rounded-md bg-white border border-{{$color}} shadow-lg">
        <ul class="overflow-auto py-1" :style="{maxHeight: maxHeight, width: dropdownWidth}">
            <template x-for="(opt, idx) in options" :key="opt.value">
                <li class="mr-2">
                    <button type="button" 
                            @click="toggleOption(opt.value)"
                            @mouseenter="hoveredIndex = idx"
                            @mouseleave="hoveredIndex = -1"
                            :class="opt.value === state.selected ? 'bg-{{$color}}/10 font-semibold text-{{$color}}' : 'text-gray-700 hover:bg-gray-100'"
                            class="w-full text-left px-3 py-2 text-sm flex items-center gap-2 relative group">
                        <!-- Checkmark -->
                        <span class="material-symbols-outlined text-xs" 
                              :class="opt.value === state.selected ? 'text-{{$color}}' : 'text-transparent'">check</span>
                        
                        <div class="flex-1 min-w-0">
                            <div x-text="opt.label" class="truncate"></div>
                        </div>

                        <!-- Tooltip for options with descriptions -->
                        <template x-if="opt.description">
                            <div class="flex-shrink-0" @click.stop>
                                <x-shared::tooltip type="help" placement="top">
                                    <span x-text="opt.description"></span>
                                </x-shared::tooltip>
                            </div>
                        </template>
                    </button>
                </li>
            </template>
            <template x-if="!options.length">
                <li class="px-3 py-2 text-sm text-gray-500" x-text="emptyText"></li>
            </template>
        </ul>
    </div>
    </template>

    <!-- Hidden input -->
    <input type="hidden" :name="name" :value="state.selected" {{ $required ? 'required' : '' }}>
</div>

@once
    @push('scripts')
        <script>
            if (!window.selectWithTooltips) {
                window.selectWithTooltips = function ({name, options, selected, emptyText, maxHeight, placeholder, color}) {
                    return {
                        name: name,
                        open: false,
                        emptyText: emptyText,
                        maxHeight: maxHeight,
                        placeholder: placeholder,
                        color: color,
                        options: options,
                        hoveredIndex: -1,
                        dropdownStyle: { left: '0px', top: '0px', width: '0px' },
                        dropdownWidth: '0px',
                        state: {
                            selected: selected ?? '',
                        },
                        init() {
                            // Keep dropdown positioned on resize/scroll
                            const handler = () => this.open && this.updatePosition();
                            this._resizeHandler = handler;
                            window.addEventListener('resize', handler);
                            window.addEventListener('scroll', handler, true);
                            this.$watch('open', (val) => { if (val) this.$nextTick(() => this.updatePosition()); });
                        },
                        destroy() {
                            window.removeEventListener('resize', this._resizeHandler);
                            window.removeEventListener('scroll', this._resizeHandler, true);
                        },
                        get selectedLabel() {
                            const opt = this.options.find(o => o.value === this.state.selected);
                            return opt ? opt.label : '';
                        },
                        updatePosition() {
                            const btn = this.$refs.button;
                            if (!btn) return;
                            const rect = btn.getBoundingClientRect();
                            const gap = 4; // small gap under the trigger
                            this.dropdownStyle = {
                                position: 'fixed',
                                left: `${Math.round(rect.left)}px`,
                                top: `${Math.round(rect.bottom + gap)}px`,
                                width: `${Math.round(rect.width)}px`,
                            };
                            this.dropdownWidth = `${Math.round(rect.width)}px`;
                        },
                        toggleOption(value) {
                            this.state.selected = this.state.selected === value ? '' : value;
                            this.open = false;
                            // Dispatch custom event for parent components to listen to
                            this.$nextTick(() => {
                                this.$el.dispatchEvent(new CustomEvent('selection-changed', {
                                    detail: { name: this.name, value: this.state.selected },
                                    bubbles: true
                                }));
                            });
                        }
                    }
                }
            }
        </script>
    @endpush
@endonce
