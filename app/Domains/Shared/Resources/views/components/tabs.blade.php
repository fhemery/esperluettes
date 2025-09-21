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

<div x-data="{ tab: @js($initialKey) }">
    <div class="border-b border-gray-200 mb-4">
        <nav class="surface-{{$color}} text-on-surface -mb-px flex flex-wrap gap-4 {{ $navClass }}" role="tablist" aria-label="Tabs">
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
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500"
                    :class="tab === @js($key)
                        ? 'selected'
                        : 'border-transparent hover:border-gray-300 {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}'"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div>
        {{ $slot }}
    </div>
</div>
