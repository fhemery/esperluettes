@props(['story' => null, 'referentials' => []])

<div class="mb-5">
    <x-input-label for="title" :value="__('story::create.form.title.label')"/>
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                  placeholder="{{ __('story::create.form.title.placeholder') }}"
                  value="{{ old('title', $story?->title ?? '') }}"/>
    <x-input-error :messages="$errors->get('title')" class="mt-2"/>
</div>

@php($selectedTypeId = old('story_ref_type_id', $story?->story_ref_type_id ?? ''))

<div class="mb-6">
    <x-input-label for="story_ref_type_id" :value="__('story::shared.type.label')"/>
    <div class="flex items-center gap-2">
        <select id="story_ref_type_id" name="story_ref_type_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value=""
                    disabled {{ $selectedTypeId === '' ? 'selected' : '' }}>{{ __('story::shared.type.placeholder') }}</option>
            @foreach(($referentials['types'] ?? collect()) as $t)
                <option
                    value="{{ $t['id'] }}" {{ (string)$selectedTypeId === (string)$t['id'] ? 'selected' : '' }}>{{ $t['name'] }}</option>
            @endforeach
        </select>
        <x-shared::tooltip type="help" :title="__('story::shared.type.label')" placement="right">
            {{ __('story::shared.type.help') }}
        </x-shared::tooltip>
    </div>
    <x-input-error :messages="$errors->get('story_ref_type_id')" class="mt-2"/>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.required') }}</p>
</div>

@php($selectedAudienceId = old('story_ref_audience_id', $story?->story_ref_audience_id ?? ''))

<div class="mb-6">
    <x-input-label for="story_ref_audience_id" :value="__('story::shared.audience.label')"/>
    <div class="flex items-center gap-2">
        <select id="story_ref_audience_id" name="story_ref_audience_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value=""
                    disabled {{ $selectedAudienceId === '' ? 'selected' : '' }}>{{ __('story::shared.audience.placeholder') }}</option>
            @foreach(($referentials['audiences'] ?? collect()) as $a)
                <option
                    value="{{ $a['id'] }}" {{ (string)$selectedAudienceId === (string)$a['id'] ? 'selected' : '' }}>{{ $a['name'] }}</option>
            @endforeach
        </select>
        <x-shared::tooltip type="help" :title="__('story::shared.audience.label')" placement="right">
            {{ __('story::shared.audience.help') }}
        </x-shared::tooltip>
    </div>
    <x-input-error :messages="$errors->get('story_ref_audience_id')" class="mt-2"/>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.required') }}</p>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.audience.note_single_select') }}</p>
</div>

<div class="mb-6">
    <x-input-label for="description" :value="__('story::shared.description.label')"/>
    <x-shared::editor
        id="story-description-editor"
        name="description"
        :max="3000"
        :nbLines="15"
        class="mt-1 block w-full"
        defaultValue="{{ old('description', $story?->description ?? '') }}"
    />
    <x-input-error :messages="$errors->get('description')" class="mt-2"/>
</div>

<div class="mb-6">
    <x-input-label for="visibility" :value="__('story::shared.visibility.label')"/>
    <div class="flex items-center gap-2">
        @php($visOld = old('visibility', $story?->visibility ?? 'public'))
        <select id="visibility" name="visibility"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option
                value="public" {{ $visOld === 'public' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.public') }}</option>
            <option
                value="community" {{ $visOld === 'community' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.community') }}</option>
            <option
                value="private" {{ $visOld === 'private' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.private') }}</option>
        </select>
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
    <x-input-error :messages="$errors->get('visibility')" class="mt-2"/>
</div>
