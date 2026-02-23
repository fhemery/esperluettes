{{--
    Themed cover tab content.
    Relies on coverForm Alpine scope: availableGenres, modalPreviewSlug, themedUrl(), selectThemed().
--}}
<div x-show="tab === 'themed'" x-cloak class="flex flex-col sm:flex-row gap-6 items-start p-4">
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
                <div class="flex justify-center items-center">
                    <x-shared::button type="button" color="accent"
                        @click="selectThemed(modalPreviewSlug); $dispatch('close-modal', 'cover-selector')">
                        {{ __('story::shared.cover.select') }}
                    </x-shared::button>
                </div>
            </div>
        </template>
        <template x-if="availableGenres.length === 0">
            <p class="text-sm text-gray-500 italic">{{ __('story::shared.cover.themed_no_genres') }}</p>
        </template>
    </div>
</div>
