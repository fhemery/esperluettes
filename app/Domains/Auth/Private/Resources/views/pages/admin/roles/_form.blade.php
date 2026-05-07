@php $isEdit = isset($role) && $role->exists; @endphp

<div class="flex flex-col gap-4"
     x-data="slugForm('{{ old('name', $role->name ?? '') }}', '{{ old('slug', $role->slug ?? '') }}', {{ $isEdit ? 'true' : 'false' }})">
    <div>
        <x-shared::input-label for="name" :required="true">{{ __('auth::admin.roles.form.name') }}</x-shared::input-label>
        <x-shared::text-input id="name" name="name" x-model="name" @blur="generateSlug()" />
        <x-shared::input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-shared::input-label for="slug" :required="true">{{ __('auth::admin.roles.form.slug') }}</x-shared::input-label>
        <x-shared::text-input id="slug" name="slug" x-model="slug" @input="slugManuallyEdited = true"
                              class="font-mono" pattern="[a-z0-9\-]+" />
        <x-shared::input-error :messages="$errors->get('slug')" />
    </div>

    <div>
        <x-shared::input-label for="description">{{ __('auth::admin.roles.form.description') }}</x-shared::input-label>
        <textarea id="description" name="description"
                  class="w-full rounded-md border border-border bg-surface-read text-fg p-2 focus:outline-none focus:ring-2 focus:ring-primary"
                  rows="3">{{ old('description', $role->description ?? '') }}</textarea>
        <x-shared::input-error :messages="$errors->get('description')" />
    </div>

    <div class="flex gap-4 pt-2">
        <x-shared::button type="submit" color="primary">{{ __('auth::admin.roles.form.save') }}</x-shared::button>
        <a href="{{ route('auth.admin.roles.index') }}">
            <x-shared::button type="button" color="secondary">{{ __('auth::admin.roles.form.cancel') }}</x-shared::button>
        </a>
    </div>
</div>
