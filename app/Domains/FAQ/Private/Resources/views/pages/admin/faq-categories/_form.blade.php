@php
    $faqCategory = $faqCategory ?? null;
    $isEdit = $faqCategory !== null;
@endphp

<div class="flex flex-col gap-6"
     x-data="slugForm('{{ old('name', $faqCategory?->name ?? '') }}', '{{ old('slug', $faqCategory?->slug ?? '') }}', {{ $isEdit ? 'true' : 'false' }})">

    <div>
        <x-shared::input-label for="name" :required="true">
            {{ __('faq::admin.categories.form.name') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="name"
            name="name"
            class="mt-1 block w-full"
            x-model="name"
            @blur="generateSlug()"
            required
            maxlength="255"
        />
        <x-shared::input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="slug" :required="true">
            {{ __('faq::admin.categories.form.slug') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="slug"
            name="slug"
            class="mt-1 block w-full font-mono"
            x-model="slug"
            @input="slugManuallyEdited = true"
            required
            maxlength="255"
            pattern="[a-z0-9\-]+"
        />
        <p class="text-xs text-fg/60 mt-1">{{ __('faq::admin.categories.form.slug_help') }}</p>
        <x-shared::input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="description">
            {{ __('faq::admin.categories.form.description') }}
        </x-shared::input-label>
        <textarea
            id="description"
            name="description"
            rows="3"
            class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface"
            maxlength="1000"
        >{{ old('description', $faqCategory?->description ?? '') }}</textarea>
        <x-shared::input-error :messages="$errors->get('description')" class="mt-1" />
    </div>

    <div>
        <x-shared::toggle
            name="is_active"
            :checked="old('is_active', $faqCategory?->is_active ?? true)"
            :label="__('faq::admin.categories.form.is_active')"
        />
        <x-shared::input-error :messages="$errors->get('is_active')" class="mt-1" />
    </div>

    <div class="flex gap-4">
        <x-shared::button type="submit" color="primary" icon="save">
            {{ $isEdit ? __('faq::admin.categories.form.update') : __('faq::admin.categories.form.create') }}
        </x-shared::button>
        <a href="{{ route('faq.admin.faq-categories.index') }}">
            <x-shared::button type="button" color="secondary">
                {{ __('faq::admin.categories.form.cancel') }}
            </x-shared::button>
        </a>
    </div>
</div>
