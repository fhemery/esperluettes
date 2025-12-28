@props(['section', 'parameters', 'open' => false])

<x-shared::collapsible :open="$open" color="transparent" textColor="fg">
    <x-slot:header>
        <div>
            <h3 class="font-semibold text-lg">{{ __($section->nameKey) }}</h3>
            @if($section->descriptionKey)
                <p class="text-sm text-fg/60 mt-0.5">{{ __($section->descriptionKey) }}</p>
            @endif
        </div>
    </x-slot:header>

    <div class="divide-y divide-border/30">
        @foreach($parameters as $param)
            <x-settings::parameter-row
                :definition="$param['definition']"
                :value="$param['value']"
                :is-overridden="$param['isOverridden']"
            />
        @endforeach
    </div>
</x-shared::collapsible>
