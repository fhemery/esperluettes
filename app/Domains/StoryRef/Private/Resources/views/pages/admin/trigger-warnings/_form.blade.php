@props(['triggerWarning' => null, 'nextOrder' => null])

@php
    $isEdit = $triggerWarning !== null;
@endphp

<div class="flex flex-col gap-6" x-data="{
    name: '{{ old('name', $triggerWarning?->name ?? '') }}',
    slug: '{{ old('slug', $triggerWarning?->slug ?? '') }}',
    slugManuallyEdited: {{ $isEdit || old('slug') ? 'true' : 'false' }},
    generateSlug() {
        if (!this.slugManuallyEdited) {
            this.slug = this.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        }
    }
}">
    <div>
        <x-shared::input-label for="name" :required="true">{{ __('story_ref::admin.trigger_warnings.form.name') }}</x-shared::input-label>
        <x-shared::text-input type="text" id="name" name="name" class="mt-1 block w-full rounded-md" x-model="name" @blur="generateSlug()" required />
        <x-shared::input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="slug" :required="true">{{ __('story_ref::admin.trigger_warnings.form.slug') }}</x-shared::input-label>
        <x-shared::text-input type="text" id="slug" name="slug" class="mt-1 block w-full rounded-md font-mono" x-model="slug" @input="slugManuallyEdited = true" required pattern="[a-z0-9\-]+" />
        <p class="text-xs text-fg/60 mt-1">{{ __('story_ref::admin.trigger_warnings.form.slug_help') }}</p>
        <x-shared::input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="description">{{ __('story_ref::admin.trigger_warnings.form.description') }}</x-shared::input-label>
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-border bg-surface text-fg focus:border-primary focus:ring-primary">{{ old('description', $triggerWarning?->description ?? '') }}</textarea>
        <x-shared::input-error :messages="$errors->get('description')" class="mt-1" />
    </div>

    @if(!$isEdit)
        <input type="hidden" name="order" value="{{ $nextOrder ?? 0 }}" />
    @endif

    <div>
        <x-shared::toggle name="is_active" :checked="old('is_active', $triggerWarning?->is_active ?? true)" :label="__('story_ref::admin.trigger_warnings.form.is_active')" />
        <x-shared::input-error :messages="$errors->get('is_active')" class="mt-1" />
    </div>

    <div class="flex gap-4">
        <x-shared::button type="submit" color="primary" icon="save">{{ $isEdit ? __('story_ref::admin.trigger_warnings.form.update') : __('story_ref::admin.trigger_warnings.form.create') }}</x-shared::button>
        <a href="{{ route('story_ref.admin.trigger-warnings.index') }}"><x-shared::button type="button" color="secondary">{{ __('story_ref::admin.trigger_warnings.form.cancel') }}</x-shared::button></a>
    </div>
</div>
