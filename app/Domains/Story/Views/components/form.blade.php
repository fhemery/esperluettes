@props(['story' => null, 'referentials' => []])

@php($selectedTypeId = old('story_ref_type_id', $story?->story_ref_type_id ?? ''))
@php($selectedGenreIds = collect(old('story_ref_genre_ids', $story?->genres?->pluck('id')->all() ?? []))->map(fn($v) => (string)$v)->all())
@php($selectedAudienceId = old('story_ref_audience_id', $story?->story_ref_audience_id ?? ''))
@php($selectedTriggerWarningIds = collect(old('story_ref_trigger_warning_ids', $story?->triggerWarnings?->pluck('id')->all() ?? []))->map(fn($v) => (string)$v)->all())
@php($selectedStatusId = old('story_ref_status_id', $story?->story_ref_status_id ?? ''))
@php($selectedFeedbackId = old('story_ref_feedback_id', $story?->story_ref_feedback_id ?? ''))
@php($selectedCopyrightId = old('story_ref_copyright_id', $story?->story_ref_copyright_id ?? ''))
@php($visOld = old('visibility', $story?->visibility ?? 'public'))

<!-- Panel 1: General info -->
<x-shared::collapsible :title="__('story::shared.panels.general')" :open="true">
    <div class="grid grid-cols-4 gap-6">
        <!-- Title -->
        <div class="col-span-4 md:col-span-3">
            <div class="flex items-center gap-2">
                <x-input-label for="title" :value="__('story::create.form.title.label')"/>
                <span class="text-red-600" aria-hidden="true">*</span>
                <x-shared::tooltip type="help" :title="__('story::create.form.title.label')" placement="right">
                    {{ __('story::create.form.title.help') ?? '' }}
                </x-shared::tooltip>
            </div>
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                          placeholder="{{ __('story::create.form.title.placeholder') }}"
                          value="{{ old('title', $story?->title ?? '') }}"/>
            <x-input-error :messages="$errors->get('title')" class="mt-2"/>
        </div>

        <!-- Type -->
        <div class="col-span-4 md:col-span-1">
            <div class="flex items-center gap-2">
                <x-input-label for="story_ref_type_id" :value="__('story::shared.type.label')"/>
                <span class="text-red-600" aria-hidden="true">*</span>
                <x-shared::tooltip type="help" :title="__('story::shared.type.label')" placement="right">
                    {{ __('story::shared.type.help') }}
                </x-shared::tooltip>
            </div>
            <select id="story_ref_type_id" name="story_ref_type_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="" disabled {{ $selectedTypeId === '' ? 'selected' : '' }}>{{ __('story::shared.type.placeholder') }}</option>
                @foreach(($referentials['types'] ?? collect()) as $t)
                    <option value="{{ $t['id'] }}" {{ (string)$selectedTypeId === (string)$t['id'] ? 'selected' : '' }}>{{ $t['name'] }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('story_ref_type_id')" class="mt-2"/>
        </div>

        <!-- Visibility -->
        <div class="col-span-4 md:col-span-2">
            <div class="flex items-center gap-2">
                <x-input-label for="visibility" :value="__('story::shared.visibility.label')"/>
                <x-shared::tooltip type="help" :title="__('story::shared.visibility.label')" placement="right">
                    <div>
                        {{ __('story::shared.visibility.help.intro') }}
                        <ul>
                            <li>{{ __('story::shared.visibility.help.public') }}</li>
                            <li>{{ __('story::shared.visibility.help.community') }}</li>
                            <li>{{ __('story::shared.visibility.help.private') }}</li>
                        </ul>
                    </div>
                </x-shared::tooltip>
            </div>
            <select id="visibility" name="visibility"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="public" {{ $visOld === 'public' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.public') }}</option>
                <option value="community" {{ $visOld === 'community' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.community') }}</option>
                <option value="private" {{ $visOld === 'private' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.private') }}</option>
            </select>
            <x-input-error :messages="$errors->get('visibility')" class="mt-2"/>
        </div>

        <!-- Copyright -->
        <div class="col-span-4 md:col-span-2">
            <div class="flex items-center gap-2">
                <x-input-label for="story_ref_copyright_id" :value="__('story::shared.copyright.label')"/>
                <span class="text-red-600" aria-hidden="true">*</span>
                <x-shared::tooltip type="help" :title="__('story::shared.copyright.label')" placement="right">
                    {{ __('story::shared.copyright.help') }}
                </x-shared::tooltip>
            </div>
            <select id="story_ref_copyright_id" name="story_ref_copyright_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="" disabled {{ $selectedCopyrightId === '' ? 'selected' : '' }}>{{ __('story::shared.copyright.placeholder') }}</option>
                @foreach(($referentials['copyrights'] ?? collect()) as $c)
                    <option value="{{ $c['id'] }}" {{ (string)$selectedCopyrightId === (string)$c['id'] ? 'selected' : '' }}>{{ $c['name'] }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('story_ref_copyright_id')" class="mt-2"/>
        </div>
    </div>
</x-shared::collapsible>

<!-- Panel 2: Details -->
<x-shared::collapsible :title="__('story::shared.panels.details')" :open="true">
    <div class="grid grid-cols-4 gap-6">
        <!-- Genres -->
        <div class="col-span-4 md:col-span-3">
            <div class="flex items-center gap-2">
                <x-input-label for="story_ref_genre_ids" :value="__('story::shared.genres.label')"/>
                <span class="text-red-600" aria-hidden="true">*</span>
                <x-shared::tooltip type="help" :title="__('story::shared.genres.label')" placement="right">
                    {{ __('story::shared.genres.help') }}
                </x-shared::tooltip>
            </div>
            <div class="mt-2">
                <x-search-multi name="story_ref_genre_ids[]" :options="$referentials['genres'] ?? []" :selected="$selectedGenreIds" valueField="id" badge="blue" />
            </div>
            <x-input-error :messages="$errors->get('story_ref_genre_ids')" class="mt-2"/>
            <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.genres.note_range') }}</p>
        </div>

        <!-- Status -->
        <div class="col-span-4 md:col-span-1">
            <div class="flex items-center gap-2">
                <x-input-label for="story_ref_status_id" :value="__('story::shared.status.label')"/>
                <x-shared::tooltip type="help" :title="__('story::shared.status.label')" placement="right">
                    {{ __('story::shared.status.help') }}
                </x-shared::tooltip>
            </div>
            <select id="story_ref_status_id" name="story_ref_status_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="" {{ $selectedStatusId === '' ? 'selected' : '' }}>— {{ __('story::shared.status.placeholder') }} —</option>
                @foreach(($referentials['statuses'] ?? collect()) as $s)
                    <option value="{{ $s['id'] }}" {{ (string)$selectedStatusId === (string)$s['id'] ? 'selected' : '' }}>{{ $s['name'] }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('story_ref_status_id')" class="mt-2"/>
        </div>
    </div>

    <!-- Summary -->
    <div class="mt-6">
        <div class="flex items-center gap-2">
            <x-input-label for="description" :value="__('story::shared.description.label')"/>
            <x-shared::tooltip type="help" :title="__('story::shared.description.label')" placement="right">
                {{ __('story::shared.description.help') ?? '' }}
            </x-shared::tooltip>
        </div>
        <x-shared::editor id="story-description-editor" name="description" :max="1000" :nbLines="15" class="mt-1 block w-full" defaultValue="{{ old('description', $story?->description ?? '') }}" />
        <x-input-error :messages="$errors->get('description')" class="mt-2"/>
    </div>
</x-shared::collapsible>

<!-- Panel 3: Audience -->
<x-shared::collapsible :title="__('story::shared.panels.audience')" :open="true">
    <div class="grid grid-cols-4 gap-6">
        <!-- Audience -->
        <div class="col-span-4 md:col-span-1">
        <div class="flex items-center gap-2">
            <x-input-label for="story_ref_audience_id" :value="__('story::shared.audience.label')"/>
            <span class="text-red-600" aria-hidden="true">*</span>
            <x-shared::tooltip type="help" :title="__('story::shared.audience.label')" placement="right">
                {{ __('story::shared.audience.help') }}
            </x-shared::tooltip>
        </div>
        <select id="story_ref_audience_id" name="story_ref_audience_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="" disabled {{ $selectedAudienceId === '' ? 'selected' : '' }}>{{ __('story::shared.audience.placeholder') }}</option>
            @foreach(($referentials['audiences'] ?? collect()) as $a)
                <option value="{{ $a['id'] }}" {{ (string)$selectedAudienceId === (string)$a['id'] ? 'selected' : '' }}>{{ $a['name'] }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('story_ref_audience_id')" class="mt-2"/>
        </div>

        <!-- Trigger warnings -->
        <div class="col-span-4 md:col-span-3">
        <div class="flex items-center gap-2">
            <x-input-label for="story_ref_trigger_warning_ids" :value="__('story::shared.trigger_warnings.label')"/>
            <x-shared::tooltip type="help" :title="__('story::shared.trigger_warnings.label')" placement="right">
                {{ __('story::shared.trigger_warnings.help') }}
            </x-shared::tooltip>
        </div>
        <div class="mt-2">
            <x-search-multi name="story_ref_trigger_warning_ids[]" :options="$referentials['trigger_warnings'] ?? []" :selected="$selectedTriggerWarningIds" valueField="id" badge="red" />
        </div>
        <x-input-error :messages="$errors->get('story_ref_trigger_warning_ids')" class="mt-2"/>
        </div>
    </div>
</x-shared::collapsible>

<!-- Panel 4: Miscellaneous -->
<x-shared::collapsible :title="__('story::shared.panels.misc')" :open="true">
    <div>
        <div class="flex items-center gap-2">
            <x-input-label for="story_ref_feedback_id" :value="__('story::shared.feedback.label')"/>
            <x-shared::tooltip type="help" :title="__('story::shared.feedback.label')" placement="right">
                {{ __('story::shared.feedback.help') }}
            </x-shared::tooltip>
        </div>
        <select id="story_ref_feedback_id" name="story_ref_feedback_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="" {{ $selectedFeedbackId === '' ? 'selected' : '' }}>— {{ __('story::shared.feedback.placeholder') }} —</option>
            @foreach(($referentials['feedbacks'] ?? collect()) as $f)
                <option value="{{ $f['id'] }}" {{ (string)$selectedFeedbackId === (string)$f['id'] ? 'selected' : '' }}>{{ $f['name'] }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('story_ref_feedback_id')" class="mt-2"/>
    </div>
</x-shared::collapsible>
