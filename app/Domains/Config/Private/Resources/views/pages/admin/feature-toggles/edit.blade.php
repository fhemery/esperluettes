<x-admin::layout>
    <div class="flex flex-col gap-6">
        <x-shared::title>{{ __('config::admin.feature_toggles.edit_title') }}</x-shared::title>

        <x-shared::flash-block />

        <div class="surface-read p-4 rounded-lg">
            <p class="text-sm text-fg/60">
                <span class="font-medium text-fg">{{ __('config::admin.feature_toggles.columns.domain') }}:</span>
                <span class="font-mono ml-1">{{ $featureToggle->domain }}</span>
            </p>
            <p class="text-sm text-fg/60 mt-1">
                <span class="font-medium text-fg">{{ __('config::admin.feature_toggles.columns.name') }}:</span>
                <span class="font-mono ml-1">{{ $featureToggle->name }}</span>
            </p>
        </div>

        <form method="POST" action="{{ route('config.admin.feature-toggles.update', $featureToggle) }}" class="flex flex-col gap-6">
            @csrf
            @method('PUT')

            <div>
                <x-shared::input-label for="admin_visibility" :required="true">{{ __('config::admin.feature_toggles.form.admin_visibility') }}</x-shared::input-label>
                <select id="admin_visibility" name="admin_visibility"
                    class="w-full border border-border rounded-lg px-3 py-2 surface-read text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="tech_admins_only" {{ old('admin_visibility', $featureToggle->admin_visibility) === 'tech_admins_only' ? 'selected' : '' }}>
                        {{ __('config::admin.feature_toggles.admin_visibility.tech_admins_only') }}
                    </option>
                    <option value="all_admins" {{ old('admin_visibility', $featureToggle->admin_visibility) === 'all_admins' ? 'selected' : '' }}>
                        {{ __('config::admin.feature_toggles.admin_visibility.all_admins') }}
                    </option>
                </select>
                <x-shared::input-error :messages="$errors->get('admin_visibility')" />
            </div>

            <div>
                <x-shared::input-label for="access" :required="true">{{ __('config::admin.feature_toggles.form.access') }}</x-shared::input-label>
                <select id="access" name="access"
                    class="w-full border border-border rounded-lg px-3 py-2 surface-read text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="off" {{ old('access', $featureToggle->access) === 'off' ? 'selected' : '' }}>OFF</option>
                    <option value="on" {{ old('access', $featureToggle->access) === 'on' ? 'selected' : '' }}>ON</option>
                    <option value="role_based" {{ old('access', $featureToggle->access) === 'role_based' ? 'selected' : '' }}>PAR RÔLE</option>
                </select>
                <x-shared::input-error :messages="$errors->get('access')" />
            </div>

            <div>
                <x-shared::input-label>{{ __('config::admin.feature_toggles.form.roles') }}</x-shared::input-label>
                <div x-data="{ currentValue: @js(old('roles', $featureToggle->roles ?? [])), saving: false }" class="mt-1">
                    <x-shared::fields.multi-select-field :options="$roles" name="roles" />
                </div>
                <x-shared::input-error :messages="$errors->get('roles')" />
            </div>

            <div class="flex gap-4">
                <x-shared::button type="submit" color="primary">{{ __('Enregistrer') }}</x-shared::button>
                <a href="{{ route('config.admin.feature-toggles.index') }}">
                    <x-shared::button type="button" color="neutral">{{ __('Annuler') }}</x-shared::button>
                </a>
            </div>
        </form>
    </div>
</x-admin::layout>
