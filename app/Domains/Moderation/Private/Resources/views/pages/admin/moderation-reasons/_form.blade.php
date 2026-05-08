@php $isEdit = isset($reason) && $reason->exists; @endphp

<div class="flex flex-col gap-6">
    <div>
        <x-shared::input-label for="topic_key" :required="true">{{ __('moderation::admin.reasons.form.topic_key') }}</x-shared::input-label>
        <select id="topic_key" name="topic_key"
            class="mt-1 block w-full rounded-md border-border bg-surface text-fg focus:border-primary focus:ring-primary">
            <option value="">{{ __('moderation::admin.reasons.form.topic_placeholder') }}</option>
            @foreach ($topics as $key => $config)
                <option value="{{ $key }}" @selected(old('topic_key', $reason->topic_key ?? '') === $key)>
                    {{ $config['displayName'] }}
                </option>
            @endforeach
        </select>
        <x-shared::input-error :messages="$errors->get('topic_key')" />
    </div>

    <div>
        <x-shared::input-label for="label" :required="true">{{ __('moderation::admin.reasons.form.label') }}</x-shared::input-label>
        <x-shared::text-input id="label" name="label" :value="old('label', $reason->label ?? '')" />
        <p class="text-xs text-fg/60 mt-1">{{ __('moderation::admin.reasons.form.label_helper') }}</p>
        <x-shared::input-error :messages="$errors->get('label')" />
    </div>

    <div>
        <x-shared::toggle
            name="is_active"
            :checked="old('is_active', $reason->is_active ?? true)"
            :label="__('moderation::admin.reasons.form.is_active')"
        />
        <p class="text-xs text-fg/60 mt-1">{{ __('moderation::admin.reasons.form.is_active_helper') }}</p>
        <x-shared::input-error :messages="$errors->get('is_active')" />
    </div>

    <div class="flex gap-4">
        <x-shared::button type="submit" color="primary" icon="save">
            {{ $isEdit ? __('moderation::admin.reasons.form.update') : __('moderation::admin.reasons.form.create') }}
        </x-shared::button>
        <a href="{{ route('moderation.admin.moderation-reasons.index') }}">
            <x-shared::button type="button" color="secondary">{{ __('moderation::admin.reasons.form.cancel') }}</x-shared::button>
        </a>
    </div>
</div>
