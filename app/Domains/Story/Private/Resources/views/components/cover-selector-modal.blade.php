@props(['themedEnabled' => false, 'customEnabled' => false, 'existingCustomCoverUrl' => null])

@php
    $tabs = [['key' => 'default', 'label' => __('story::shared.cover.tab_default')]];
    if ($themedEnabled) {
        $tabs[] = ['key' => 'themed', 'label' => __('story::shared.cover.tab_themed')];
    }
    if ($customEnabled) {
        $tabs[] = ['key' => 'custom', 'label' => __('story::shared.cover.tab_custom')];
    }
@endphp

<x-shared::modal name="cover-selector" maxWidth="2xl">
    <div class="p-6">
        <h2 class="text-lg font-semibold mb-4">{{ __('story::shared.cover.modal_title') }}</h2>
        <p class="text-sm text-fg mb-6">{{ __('story::shared.cover.modal_description') }}</p>

        <div class="border-primary border">
            <x-shared::tabs color="primary" :tabs="$tabs" initial="default">
                <x-story::cover-tab-default />

                @if ($themedEnabled)
                    <x-story::cover-tab-themed />
                @endif

                @if ($customEnabled)
                    <x-story::cover-tab-custom :existingCustomCoverUrl="$existingCustomCoverUrl" />
                @endif
            </x-shared::tabs>
        </div>

        {{-- Cancel --}}
        <div class="mt-6 flex justify-end">
            <x-shared::button type="button" color="neutral" :outline="true"
                @click="$dispatch('close-modal', 'cover-selector')">
                {{ __('story::shared.cover.cancel') }}
            </x-shared::button>
        </div>
    </div>
</x-shared::modal>
