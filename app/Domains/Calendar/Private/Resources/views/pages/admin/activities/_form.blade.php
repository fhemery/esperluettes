@php
    $activity = $activity ?? null;
    $isEdit = $activity !== null;
@endphp

{{-- Section: Activity details --}}
<div class="surface-bg p-6 rounded-lg flex flex-col gap-4">
    <h2 class="text-base font-semibold">{{ __('calendar::admin.sections.details') }}</h2>

    <div>
        <x-shared::input-label for="name" :required="true">{{ __('calendar::admin.fields.name') }}</x-shared::input-label>
        <x-shared::text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $activity?->name ?? '')" maxlength="200" required />
        <x-shared::input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="activity_type" :required="!$isEdit">{{ __('calendar::admin.fields.activity_type') }}</x-shared::input-label>
        @if($isEdit)
            @php
                $typeLabel = __('calendar::activities.' . $activity->activity_type);
                if ($typeLabel === 'calendar::activities.' . $activity->activity_type) {
                    $typeLabel = $activity->activity_type;
                }
            @endphp
            <p class="mt-1 text-sm text-fg/70 font-medium">{{ $typeLabel }}</p>
        @else
            <select id="activity_type" name="activity_type"
                class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface"
                required>
                <option value="">—</option>
                @foreach ($activityTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('activity_type') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <x-shared::input-error :messages="$errors->get('activity_type')" class="mt-1" />
        @endif
    </div>

    <div>
        <x-shared::input-label for="description">{{ __('calendar::admin.fields.description') }}</x-shared::input-label>
        <x-shared::editor
            name="description"
            id="description"
            :defaultValue="old('description', $activity?->description ?? '')"
            :nbLines="10"
            :resizable="true"
            :withLinks="true"
            class="mt-1"
        />
        <x-shared::input-error :messages="$errors->get('description')" class="mt-1" />
    </div>
</div>

{{-- Section: Media --}}
<div class="surface-bg p-6 rounded-lg flex flex-col gap-4">
    <h2 class="text-base font-semibold">{{ __('calendar::admin.sections.media') }}</h2>

    <x-shared::image-upload
        name="image"
        id="image"
        :currentPath="$activity?->image_path"
        :label="__('calendar::admin.fields.image')"
        :helpText="__('shared::image-upload.max_size', ['size' => 5])"
    />
</div>

{{-- Section: Restrictions & Settings --}}
<div class="surface-bg p-6 rounded-lg flex flex-col gap-4">
    <h2 class="text-base font-semibold">{{ __('calendar::admin.sections.restrictions') }}</h2>

    <div>
        <x-shared::input-label>{{ __('calendar::admin.fields.role_restrictions') }}</x-shared::input-label>
        <div class="mt-1">
            <x-shared::searchable-multi-select
                name="role_restrictions[]"
                :options="$roleOptions"
                :selected="old('role_restrictions', $activity?->role_restrictions ?? [])"
                valueField="slug"
                :placeholder="__('Choisir des rôles...')"
            />
        </div>
        <x-shared::input-error :messages="$errors->get('role_restrictions')" class="mt-1" />
    </div>

    <div>
        <x-shared::toggle
            name="requires_subscription"
            :checked="(bool) old('requires_subscription', $activity?->requires_subscription ?? false)"
            :label="__('calendar::admin.fields.requires_subscription')"
        />
    </div>

    <div>
        <x-shared::input-label for="max_participants">{{ __('calendar::admin.fields.max_participants') }}</x-shared::input-label>
        <x-shared::text-input id="max_participants" name="max_participants" type="number"
            class="mt-1 block w-48" min="1" max="100000"
            :value="old('max_participants', $activity?->max_participants ?? '')" />
        <x-shared::input-error :messages="$errors->get('max_participants')" class="mt-1" />
    </div>
</div>

{{-- Section: Dates --}}
<div class="surface-bg p-6 rounded-lg flex flex-col gap-4">
    <h2 class="text-base font-semibold">{{ __('calendar::admin.sections.dates') }}</h2>
    <p class="text-xs text-fg/60">{{ __('calendar::admin.timezone_hint') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-shared::input-label for="preview_starts_at">{{ __('calendar::admin.fields.preview_starts_at') }}</x-shared::input-label>
            <x-shared::text-input id="preview_starts_at" name="preview_starts_at" type="datetime-local"
                class="mt-1 block w-full"
                :value="old('preview_starts_at', $activity?->preview_starts_at?->format('Y-m-d\TH:i') ?? '')" />
            <x-shared::input-error :messages="$errors->get('preview_starts_at')" class="mt-1" />
        </div>

        <div>
            <x-shared::input-label for="active_starts_at">{{ __('calendar::admin.fields.active_starts_at') }}</x-shared::input-label>
            <x-shared::text-input id="active_starts_at" name="active_starts_at" type="datetime-local"
                class="mt-1 block w-full"
                :value="old('active_starts_at', $activity?->active_starts_at?->format('Y-m-d\TH:i') ?? '')" />
            <x-shared::input-error :messages="$errors->get('active_starts_at')" class="mt-1" />
        </div>

        <div>
            <x-shared::input-label for="active_ends_at">{{ __('calendar::admin.fields.active_ends_at') }}</x-shared::input-label>
            <x-shared::text-input id="active_ends_at" name="active_ends_at" type="datetime-local"
                class="mt-1 block w-full"
                :value="old('active_ends_at', $activity?->active_ends_at?->format('Y-m-d\TH:i') ?? '')" />
            <x-shared::input-error :messages="$errors->get('active_ends_at')" class="mt-1" />
        </div>

        <div>
            <x-shared::input-label for="archived_at">{{ __('calendar::admin.fields.archived_at') }}</x-shared::input-label>
            <x-shared::text-input id="archived_at" name="archived_at" type="datetime-local"
                class="mt-1 block w-full"
                :value="old('archived_at', $activity?->archived_at?->format('Y-m-d\TH:i') ?? '')" />
            <x-shared::input-error :messages="$errors->get('archived_at')" class="mt-1" />
        </div>
    </div>
</div>

<div class="flex gap-4">
    <x-shared::button type="submit" color="primary" icon="save">
        {{ $isEdit ? __('Mettre à jour') : __('Créer') }}
    </x-shared::button>
    <a href="{{ route('calendar.admin.activities.index') }}">
        <x-shared::button type="button" color="secondary">{{ __('Annuler') }}</x-shared::button>
    </a>
</div>
