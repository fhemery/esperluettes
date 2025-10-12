@props([
    // Array of tabs: [['key' => 'about', 'label' => 'About', 'disabled' => false]]
    'tabs' => [],
    // Initial active tab key
    'initial' => null,
    // Optional extra classes for the nav container
    'navClass' => '',
    'color' => 'neutral'
])

@php
    $tabs = collect($tabs)->map(function ($t) {
        return array_merge([
            'key' => '',
            'label' => '',
            'disabled' => false,
        ], (array) $t);
    })->values();
    $initialKey = $initial ?? ($tabs->first()['key'] ?? '');
@endphp

<div x-data="{ tab: @js($initialKey) }" class="flex-1 w-full">
    <div>
        <nav class="surface-{{$color}} text-on-surface flex w-full gap-4 {{ $navClass }}" role="tablist" aria-label="Tabs">
            @foreach($tabs as $t)
                @php($key = (string) $t['key'])
                @php($label = (string) $t['label'])
                @php($disabled = (bool) ($t['disabled'] ?? false))
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === @js($key) ? 'true' : 'false'"
                    :tabindex="tab === @js($key) ? '0' : '-1'"
                    @click="if (!{{ $disabled ? 'true' : 'false' }}) tab = @js($key)"
                    @keydown.arrow-right.prevent="
                        const buttons = Array.from($el.parentElement.querySelectorAll('button[role=\'tab\']'));
                        const i = buttons.indexOf($el);
                        const next = buttons[(i + 1) % buttons.length];
                        next.focus(); next.click();
                    "
                    @keydown.arrow-left.prevent="
                        const buttons = Array.from($el.parentElement.querySelectorAll('button[role=\'tab\']'));
                        const i = buttons.indexOf($el);
                        const prev = buttons[(i - 1 + buttons.length) % buttons.length];
                        prev.focus(); prev.click();
                    "
                    class="flex-1 whitespace-nowrap py-3 px-1 border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    :class="tab === @js($key)
                        ? 'selected border-none'
                        : 'border-transparent {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}'"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="p-2">
        {{ $slot }}
    </div>
</div>
