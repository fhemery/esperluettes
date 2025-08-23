@props(['story' => null])

<div class="mb-5">
    <x-input-label for="title" :value="__('story::create.form.title.label')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" placeholder="{{ __('story::create.form.title.placeholder') }}" value="{{ old('title', $story->title ?? '') }}" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mb-6">
    <x-input-label for="description" :value="__('story::shared.description.label')" />
    <x-shared::editor
        id="story-description-editor"
        name="description"
        :max="3000"
        :nbLines="15"
        class="mt-1 block w-full"
        defaultValue="{{ old('description', $story->description ?? '') }}"
    />
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mb-6">
    <x-input-label for="visibility" :value="__('story::shared.visibility.label')" />
    <div class="flex items-center gap-2">
        @php $visOld = old('visibility', $story->visibility ?? 'public'); @endphp
        <select id="visibility" name="visibility" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="public" {{ $visOld === 'public' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.public') }}</option>
            <option value="community" {{ $visOld === 'community' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.community') }}</option>
            <option value="private" {{ $visOld === 'private' ? 'selected' : '' }}>{{ __('story::shared.visibility.options.private') }}</option>
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
    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
</div>
