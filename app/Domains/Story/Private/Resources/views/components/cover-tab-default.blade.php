{{--
    Default cover tab content.
    Emits no events; calls selectDefault() + closes modal directly via Alpine parent scope.
--}}
<div x-show="tab === 'default'" class="flex flex-col sm:flex-row gap-6 items-center p-4 h-full">
    <div class="flex-shrink-0 mx-auto">
        <x-shared::default-cover class="w-[150px] object-contain" />
    </div>
    <div class="flex flex-col gap-4 flex-1 h-full justify-between">
        <p class="text-sm text-fg">{{ __('story::shared.cover.default_description') }}</p>
        <div class="flex-1">&nbsp;</div>
        <div class="flex justify-center mt-auto">
            <x-shared::button type="button" color="accent"
                @click="selectDefault(); $dispatch('close-modal', 'cover-selector')">
                {{ __('story::shared.cover.select_default') }}
            </x-shared::button>
        </div>
    </div>
</div>
