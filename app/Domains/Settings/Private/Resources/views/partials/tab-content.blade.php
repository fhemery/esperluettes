@if(!$tab || empty($sections))
    <div class="p-8 text-center">
        <p class="text-fg/50">{{ __('settings::settings.no_settings_in_tab') }}</p>
    </div>
@else
    <div class="divide-y divide-border">
        @foreach($sections as $index => $sectionData)
            <x-settings::section
                :section="$sectionData['section']"
                :parameters="$sectionData['parameters']"
                :open="$index === 0"
            />
        @endforeach
    </div>
@endif
