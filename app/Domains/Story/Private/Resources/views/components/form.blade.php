@props(['story' => null, 'referentials' => []])

@php($selectedTypeId = old('story_ref_type_id', $story?->story_ref_type_id ?? ''))
@php($selectedGenreIds = collect(old('story_ref_genre_ids', $story?->genres?->pluck('id')->all() ?? []))->map(fn($v) => (string)$v)->all())
@php($selectedAudienceId = old('story_ref_audience_id', $story?->story_ref_audience_id ?? ''))
@php($selectedTriggerWarningIds = collect(old('story_ref_trigger_warning_ids', $story?->triggerWarnings?->pluck('id')->all() ?? []))->map(fn($v) => (string)$v)->all())
@php($selectedStatusId = old('story_ref_status_id', $story?->story_ref_status_id ?? ''))
@php($selectedFeedbackId = old('story_ref_feedback_id', $story?->story_ref_feedback_id ?? ''))
@php($selectedCopyrightId = old('story_ref_copyright_id', $story?->story_ref_copyright_id ?? ''))
@php($visOld = old('visibility', $story?->visibility ?? 'public'))
@php($visibilityOptions = [
['value' => 'public', 'label' => __('story::shared.visibility.options.public'), 'description' => __('story::shared.visibility.help.public')],
['value' => 'community', 'label' => __('story::shared.visibility.options.community'), 'description' => __('story::shared.visibility.help.community')],
['value' => 'private', 'label' => __('story::shared.visibility.options.private'), 'description' => __('story::shared.visibility.help.private')],
])
@php($twDisclosureOld = old('tw_disclosure', $story?->tw_disclosure ?? ''))
@php($twDisclosureOptions = [
['value' => 'listed', 'label' => __('story::shared.trigger_warnings.form_options.listed'), 'description' => __('story::shared.trigger_warnings.listed_help')],
['value' => 'no_tw', 'label' => __('story::shared.trigger_warnings.form_options.no_tw'), 'description' => __('story::shared.trigger_warnings.no_tw_help')],
['value' => 'unspoiled', 'label' => __('story::shared.trigger_warnings.form_options.unspoiled'), 'description' => __('story::shared.trigger_warnings.unspoiled_help')],
])

<div class="flex flex-col gap-4">
    <!-- Panel 1: General info -->
    <x-shared::collapsible :title="__('story::shared.panels.general')" :open="true">
        <div class="grid grid-cols-4 gap-6">
            <!-- Title -->
            <div class="col-span-4 md:col-span-3 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-input-label :required="true" for="title" :value="__('story::shared.title.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.title.label')" placement="right">
                        {{ __('story::shared.title.help') ?? '' }}
                    </x-shared::tooltip>
                </div>
                <x-text-input id="title" name="title" type="text" class="block w-full bg-transparent"
                    placeholder="{{ __('story::shared.title.placeholder') }}"
                    value="{{ old('title', $story?->title ?? '') }}" />
                <x-input-error :messages="$errors->get('title')" />
            </div>

            <!-- Type -->
            <div class="col-span-4 md:col-span-1 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-input-label :required="true" for="story_ref_type_id" :value="__('story::shared.type.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.type.label')" placement="right">
                        {{ __('story::shared.type.help') }}
                    </x-shared::tooltip>
                </div>
                <x-shared::select-with-tooltips name="story_ref_type_id" :options="$referentials['types'] ?? []" :selected="$selectedTypeId"
                    :placeholder="__('story::shared.type.placeholder')" valueField="id" labelField="name" descriptionField="description"
                    :required="true" color="accent" :truncateValues="false" />
                <x-input-error :messages="$errors->get('story_ref_type_id')" class="mt-2" />
            </div>

            <!-- Visibility -->
            <div class="col-span-4 md:col-span-2 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-input-label :required="true" for="visibility" :value="__('story::shared.visibility.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.visibility.label')" placement="right">
                        {{ __('story::shared.visibility.help.intro') }}
                    </x-shared::tooltip>
                </div>
                <x-shared::select-with-tooltips name="visibility" :options="$visibilityOptions" :selected="$visOld"
                    valueField="value" labelField="label" descriptionField="description" color="accent" />
                <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
            </div>

            <!-- Copyright -->
            <div class="col-span-4 md:col-span-2 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-input-label :required="true" for="story_ref_copyright_id" :value="__('story::shared.copyright.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.copyright.label')" placement="right">
                        {{ __('story::shared.copyright.help') }}
                    </x-shared::tooltip>
                </div>
                <x-shared::select-with-tooltips name="story_ref_copyright_id" :options="$referentials['copyrights'] ?? []" :selected="$selectedCopyrightId"
                    :placeholder="__('story::shared.copyright.placeholder')" valueField="id" labelField="name" descriptionField="description"
                    :required="true" color="accent" />
                <x-input-error :messages="$errors->get('story_ref_copyright_id')" class="mt-2" />
            </div>
        </div>
    </x-shared::collapsible>

    <!-- Panel 2: Details -->
    <x-shared::collapsible :title="__('story::shared.panels.details')" :open="true">
        <div class="grid grid-cols-4 gap-6">
            <!-- Genres -->
            <div class="col-span-4 md:col-span-2 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-input-label :required="true" for="story_ref_genre_ids" :value="__('story::shared.genres.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.genres.label')" placement="right">
                        {{ __('story::shared.genres.help') }}
                    </x-shared::tooltip>
                </div>
                <div>
                    <x-shared::searchable-multi-select name="story_ref_genre_ids[]" :options="$referentials['genres'] ?? []"
                        :selected="$selectedGenreIds" valueField="id" descriptionField="description" color="accent" />
                </div>
                <x-input-error :messages="$errors->get('story_ref_genre_ids')" class="mt-2" />
                <p class="text-xs text-gray-500">{{ __('story::shared.genres.note_range') }}</p>
            </div>

            <!-- Status -->
            <div class="col-span-4 md:col-span-2 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-input-label for="story_ref_status_id" :value="__('story::shared.status.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.status.label')" placement="right">
                        {{ __('story::shared.status.help') }}
                    </x-shared::tooltip>
                </div>
                <x-shared::select-with-tooltips name="story_ref_status_id" :options="$referentials['statuses'] ?? []" :selected="$selectedStatusId"
                    :placeholder="__('story::shared.status.placeholder')" valueField="id" labelField="name" descriptionField="description"
                    color="accent" />
                <x-input-error :messages="$errors->get('story_ref_status_id')" class="mt-2" />
            </div>

            <!-- Summary -->
            <div class="col-span-4">
                <div class="flex items-center gap-2">
                    <x-input-label for="description" :value="__('story::shared.description.label')" />
                    <span class="text-red-600" aria-hidden="true">*</span>
                    <x-shared::tooltip type="help" :title="__('story::shared.description.label')" placement="right">
                        {{ __('story::shared.description.help') ?? '' }}
                    </x-shared::tooltip>
                </div>
                <x-shared::editor id="story-description-editor" name="description" :min="100" :max="1000"
                    :nbLines="10" defaultValue="{{ old('description', $story?->description ?? '') }}" />
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </x-shared::collapsible>

    <!-- Panel 3: Audience -->
    <x-shared::collapsible :title="__('story::shared.panels.audience')" :open="true">
        <div class="grid grid-cols-4 gap-6">
            <!-- Audience -->
            <div class="col-span-4 md:col-span-2 flex gap-2">
                <div class="flex items-center gap-2 no-wrap">
                    <x-input-label :required="true" for="story_ref_audience_id" :value="__('story::shared.audience.label')" />
                    <x-shared::tooltip type="help" :title="__('story::shared.audience.label')" placement="right">
                        {{ __('story::shared.audience.help') }}
                    </x-shared::tooltip>
                </div>
                <x-shared::select-with-tooltips name="story_ref_audience_id" :options="$referentials['audiences'] ?? []" :selected="$selectedAudienceId"
                    :placeholder="__('story::shared.audience.placeholder')" valueField="id" labelField="name" descriptionField="description"
                    :required="true" color="accent" />
                <x-input-error :messages="$errors->get('story_ref_audience_id')" />
            </div>

            <!-- Trigger warnings -->
            <div class="col-span-4 flex flex-col gap-2" x-data="{ discl: @js($twDisclosureOld) }">
                <div class="flex flex-col sm:flex-row gap-2 w-full">
                    <div class="flex items-center gap-2">
                        <x-input-label for="tw_disclosure" :required="true" :value="__('story::shared.trigger_warnings.label')" />
                        <x-shared::tooltip type="help" :title="__('story::shared.trigger_warnings.label')" placement="right">
                            {{ __('story::shared.trigger_warnings.help') }}
                        </x-shared::tooltip>
                    </div>
                    <div class="min-w-32">
                        <x-shared::select-with-tooltips name="tw_disclosure" :options="$twDisclosureOptions" :selected="$twDisclosureOld"
                            valueField="value" labelField="label" descriptionField="description"
                            placeholder="{{ __('story::shared.trigger_warnings.tw_disclosure_placeholder') }}"
                            color="accent"
                            @selection-changed.window="if ($event.detail?.name === 'tw_disclosure') discl = $event.detail.value" />
                    </div>
                    <div x-cloak x-show="discl === 'listed'" class="flex-1">
                        <x-shared::searchable-multi-select name="story_ref_trigger_warning_ids[]" :options="$referentials['trigger_warnings'] ?? []"
                            :selected="$selectedTriggerWarningIds" valueField="id" descriptionField="description"
                            placeholder="{{ __('story::shared.trigger_warnings.placeholder') }}" color="accent" />
                    </div>
                    <x-input-error :messages="$errors->get('story_ref_trigger_warning_ids')" />
                    <x-input-error :messages="$errors->get('tw_disclosure')" />
                </div>
            </div>
        </div>
    </x-shared::collapsible>



    <!-- Panel 4: Miscellaneous -->
    <x-shared::collapsible :title="__('story::shared.panels.misc')" :open="true">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <x-input-label for="story_ref_feedback_id" :value="__('story::shared.feedback.label')" />
                <x-shared::tooltip type="help" :title="__('story::shared.feedback.label')" placement="right">
                    {{ __('story::shared.feedback.help') }}
                </x-shared::tooltip>
            </div>
            <x-shared::select-with-tooltips name="story_ref_feedback_id" :options="$referentials['feedbacks'] ?? []" :selected="$selectedFeedbackId"
                :placeholder="__('story::shared.feedback.placeholder')" valueField="id" labelField="name" descriptionField="description"
                color="accent" />
            <x-input-error :messages="$errors->get('story_ref_feedback_id')" />
        </div>
    </x-shared::collapsible>
</div>
