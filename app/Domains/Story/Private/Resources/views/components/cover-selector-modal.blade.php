@props(['defaultCoverUrl' => asset('images/story/default-cover.svg'), 'themedEnabled' => false])

<x-shared::modal name="cover-selector" maxWidth="2xl">
    <div class="p-6">
        <h2 class="text-lg font-semibold mb-4">{{ __('story::shared.cover.modal_title') }}</h2>

        @php
            $tabs = [['key' => 'default', 'label' => __('story::shared.cover.tab_default')]];
            if ($themedEnabled) {
                $tabs[] = ['key' => 'themed', 'label' => __('story::shared.cover.tab_themed')];
            }
        @endphp
        <x-shared::tabs color="primary" :tabs="$tabs" initial="default">

            {{-- Default tab --}}
            <div x-show="tab === 'default'" class="flex flex-col sm:flex-row gap-6 items-start pt-4">
                <div class="flex-shrink-0">
                    <x-shared::default-cover class="w-[150px] object-contain" />
                </div>
                <div class="flex flex-col gap-4">
                    <p class="text-sm text-fg">{{ __('story::shared.cover.default_description') }}</p>
                    <x-shared::button type="button" color="accent"
                        @click="selectDefault(); $dispatch('close-modal', 'cover-selector')">
                        {{ __('story::shared.cover.select') }}
                    </x-shared::button>
                </div>
            </div>

            {{-- Themed tab --}}
            @if($themedEnabled)
            <div x-show="tab === 'themed'" x-cloak class="flex flex-col sm:flex-row gap-6 items-start pt-4">
                <div class="flex-shrink-0 w-[150px]">
                    <template x-if="modalPreviewSlug">
                        <img :src="themedUrl(modalPreviewSlug)" alt="" class="w-[150px] object-contain" loading="lazy" />
                    </template>
                    <template x-if="!modalPreviewSlug">
                        <div class="w-[150px] h-[200px] bg-gray-100 flex items-center justify-center rounded">
                            <span class="material-symbols-outlined text-gray-300 text-4xl">image</span>
                        </div>
                    </template>
                </div>
                <div class="flex flex-col gap-4 flex-1">
                    <p class="text-sm text-fg">{{ __('story::shared.cover.themed_description') }}</p>

                    <template x-if="availableGenres.length > 0">
                        <div class="flex flex-col gap-3">
                            <label class="text-sm font-medium">{{ __('story::shared.cover.themed_select_genre') }}</label>
                            <select x-model="modalPreviewSlug"
                                class="rounded-md border border-accent bg-transparent text-sm focus:border-accent focus:ring-accent/10">
                                <template x-for="g in availableGenres" :key="g.slug">
                                    <option :value="g.slug" x-text="g.name"></option>
                                </template>
                            </select>
                            <x-shared::button type="button" color="accent"
                                @click="selectThemed(modalPreviewSlug); $dispatch('close-modal', 'cover-selector')">
                                {{ __('story::shared.cover.select') }}
                            </x-shared::button>
                        </div>
                    </template>
                    <template x-if="availableGenres.length === 0">
                        <p class="text-sm text-gray-500 italic">{{ __('story::shared.cover.themed_no_genres') }}</p>
                    </template>
                </div>
            </div>
            @endif

        </x-shared::tabs>

        {{-- Cancel --}}
        <div class="mt-6 flex justify-end">
            <x-shared::button type="button" color="neutral" :outline="true"
                @click="$dispatch('close-modal', 'cover-selector')">
                {{ __('story::shared.cover.cancel') }}
            </x-shared::button>
        </div>
    </div>
</x-shared::modal>
