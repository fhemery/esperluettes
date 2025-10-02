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
    })" class="relative w-full" @click.outside="open = false">
    
    <!-- Display Button -->
    <button type="button" @click="open = !open" 
            class="w-full flex items-center gap-2 justify-between text-left px-3 py-2 rounded-md border border-{{$color}} hover:border-{{$color}}/80 focus:outline-none focus:ring-2 focus:ring-{{$color}}/50 flex items-center justify-between">
        <span x-text="selectedLabel || @js($placeholder)" 
              :class="selectedLabel ? 'text-{{$color}} font-semibold' : 'text-{{$color}}/70'" class="flex-1 truncate"></span>
        <span class="material-symbols-outlined text-{{$color}} transition-transform" 
              :class="open ? 'rotate-180' : ''">expand_more</span>
    </button>

    <!-- Dropdown -->
    <div x-cloak x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-30 mt-1 w-full rounded-md bg-white border border-{{$color}} shadow-lg">
        <ul class="overflow-auto py-1" :style="{maxHeight: maxHeight}">
            <template x-for="(opt, idx) in options" :key="opt.value">
                <li>
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
                        state: {
                            selected: selected ?? '',
                        },
                        get selectedLabel() {
                            const opt = this.options.find(o => o.value === this.state.selected);
                            return opt ? opt.label : '';
                        },
                        toggleOption(value) {
                            this.state.selected = this.state.selected === value ? '' : value;
                            this.open = false;
                            // Dispatch custom event for parent components to listen to
                            this.$nextTick(() => {
                                this.$el.dispatchEvent(new CustomEvent('selection-changed', {
                                    detail: { value: this.state.selected },
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
