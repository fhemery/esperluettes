@props(['audience' => null, 'nextOrder' => null])

@php
    $isEdit = $audience !== null;
@endphp

<div class="flex flex-col gap-6">
    <!-- Name -->
    <div>
        <x-shared::input-label for="name" :required="true">
            {{ __('story_ref::admin.audiences.form.name') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="name"
            name="name"
            class="mt-1 block w-full rounded-md"
            :value="old('name', $audience?->name ?? '')"
            required
        />
        <x-shared::input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <!-- Slug -->
    <div>
        <x-shared::input-label for="slug" :required="true">
            {{ __('story_ref::admin.audiences.form.slug') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="slug"
            name="slug"
            class="mt-1 block w-full rounded-md font-mono"
            :value="old('slug', $audience?->slug ?? '')"
            required
            pattern="[a-z0-9\-]+"
        />
        <p class="text-xs text-fg/60 mt-1">{{ __('story_ref::admin.audiences.form.slug_help') }}</p>
        <x-shared::input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <!-- Order -->
    <div>
        <x-shared::input-label for="order" :required="true">
            {{ __('story_ref::admin.audiences.form.order') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="number"
            id="order"
            name="order"
            class="mt-1 block w-32 rounded-md"
            :value="old('order', $audience?->order ?? $nextOrder ?? 0)"
            required
            min="0"
        />
        <x-shared::input-error :messages="$errors->get('order')" class="mt-1" />
    </div>

    <!-- Threshold Age -->
    <div>
        <x-shared::input-label for="threshold_age">
            {{ __('story_ref::admin.audiences.form.threshold_age') }}
        </x-shared::input-label>
        <div class="flex items-center gap-2 mt-1">
            <x-shared::text-input
                type="number"
                id="threshold_age"
                name="threshold_age"
                class="block w-24 rounded-md"
                :value="old('threshold_age', $audience?->threshold_age ?? '')"
                min="1"
                max="99"
                placeholder="18"
            />
            <span class="text-fg/60">{{ __('story_ref::admin.audiences.form.years_old') }}</span>
        </div>
        <p class="text-xs text-fg/60 mt-1">{{ __('story_ref::admin.audiences.form.threshold_help') }}</p>
        <x-shared::input-error :messages="$errors->get('threshold_age')" class="mt-1" />
    </div>

    <!-- Is Active -->
    <div>
        <x-shared::toggle
            name="is_active"
            :checked="old('is_active', $audience?->is_active ?? true)"
            :label="__('story_ref::admin.audiences.form.is_active')"
        />
        <x-shared::input-error :messages="$errors->get('is_active')" class="mt-1" />
    </div>

    <hr class="border-border" />

    <!-- Mature Content Section -->
    <div class="surface-read p-4 rounded-lg">
        <h3 class="font-medium text-lg mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-warning">shield</span>
            {{ __('story_ref::admin.audiences.form.mature_section') }}
        </h3>

        <!-- Is Mature Audience -->
        <div>
            <x-shared::toggle
                name="is_mature_audience"
                :checked="old('is_mature_audience', $audience?->is_mature_audience ?? false)"
                :label="__('story_ref::admin.audiences.form.is_mature_audience')"
            />
            <p class="text-xs text-fg/60 mt-1 ml-14">{{ __('story_ref::admin.audiences.form.is_mature_help') }}</p>
            <x-shared::input-error :messages="$errors->get('is_mature_audience')" class="mt-1" />
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-4">
        <x-shared::button type="submit" color="primary" icon="save">
            {{ $isEdit ? __('story_ref::admin.audiences.form.update') : __('story_ref::admin.audiences.form.create') }}
        </x-shared::button>
        <a href="{{ route('story_ref.admin.audiences.index') }}">
            <x-shared::button type="button" color="secondary">
                {{ __('story_ref::admin.audiences.form.cancel') }}
            </x-shared::button>
        </a>
    </div>
</div>
