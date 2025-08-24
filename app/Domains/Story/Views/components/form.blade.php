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

@php($selectedGenreIds = collect(old('story_ref_genre_ids', $story?->genres?->pluck('id')->all() ?? []))->map(fn($v) => (string)$v)->all())

<div class="mb-6">
    <x-input-label for="story_ref_genre_ids" :value="__('story::shared.genres.label')"/>
    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach(($referentials['genres'] ?? collect()) as $g)
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="story_ref_genre_ids[]" value="{{ $g['id'] }}"
                       {{ in_array((string)$g['id'], $selectedGenreIds, true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                <span>{{ $g['name'] }}</span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('story_ref_genre_ids')" class="mt-2"/>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.genres.note_range') }}</p>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.required') }}</p>
    <x-shared::tooltip type="help" :title="__('story::shared.genres.label')" placement="right">
        {{ __('story::shared.genres.help') }}
    </x-shared::tooltip>
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

@php($selectedStatusId = old('story_ref_status_id', $story?->story_ref_status_id ?? ''))

<div class="mb-6">
    <x-input-label for="story_ref_status_id" :value="__('story::shared.status.label')"/>
    <div class="flex items-center gap-2">
        <select id="story_ref_status_id" name="story_ref_status_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="" {{ $selectedStatusId === '' ? 'selected' : '' }}>— {{ __('story::shared.status.placeholder') }} —</option>
            @foreach(($referentials['statuses'] ?? collect()) as $s)
                <option value="{{ $s['id'] }}" {{ (string)$selectedStatusId === (string)$s['id'] ? 'selected' : '' }}>{{ $s['name'] }}</option>
            @endforeach
        </select>
        <x-shared::tooltip type="help" :title="__('story::shared.status.label')" placement="right">
            {{ __('story::shared.status.help') }}
        </x-shared::tooltip>
    </div>
    <x-input-error :messages="$errors->get('story_ref_status_id')" class="mt-2"/>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.optional') }}</p>
</div>

@php($selectedCopyrightId = old('story_ref_copyright_id', $story?->story_ref_copyright_id ?? ''))

<div class="mb-6">
    <x-input-label for="story_ref_copyright_id" :value="__('story::shared.copyright.label')"/>
    <div class="flex items-center gap-2">
        <select id="story_ref_copyright_id" name="story_ref_copyright_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value=""
                    disabled {{ $selectedCopyrightId === '' ? 'selected' : '' }}>{{ __('story::shared.copyright.placeholder') }}</option>
            @foreach(($referentials['copyrights'] ?? collect()) as $c)
                <option
                    value="{{ $c['id'] }}" {{ (string)$selectedCopyrightId === (string)$c['id'] ? 'selected' : '' }}>{{ $c['name'] }}</option>
            @endforeach
        </select>
        <x-shared::tooltip type="help" :title="__('story::shared.copyright.label')" placement="right">
            {{ __('story::shared.copyright.help') }}
        </x-shared::tooltip>
    </div>
    <x-input-error :messages="$errors->get('story_ref_copyright_id')" class="mt-2"/>
    <p class="mt-1 text-xs text-gray-500">{{ __('story::shared.required') }}</p>
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
