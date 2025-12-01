<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('auth.admin.users.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('auth::admin.users.edit_title', ['name' => $displayName ?? $user->email]) }}</x-shared::title>
        </div>

        <form action="{{ route('auth.admin.users.update', $user) }}" method="POST" class="surface-bg p-6 rounded-lg">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-6">
                <!-- Display Name (read-only) -->
                <div>
                    <x-shared::input-label for="display_name">
                        {{ __('admin::auth.users.name_header') }}
                    </x-shared::input-label>
                    <x-shared::text-input
                        type="text"
                        id="display_name"
                        class="mt-1 block w-full bg-surface-read"
                        :value="$displayName ?? '-'"
                        disabled
                    />
                    <p class="text-xs text-fg/60 mt-1">{{ __('auth::admin.users.form.display_name_help') }}</p>
                </div>

                <!-- Email -->
                <div>
                    <x-shared::input-label for="email" :required="true">
                        {{ __('admin::auth.users.email_header') }}
                    </x-shared::input-label>
                    <x-shared::text-input
                        type="email"
                        id="email"
                        name="email"
                        class="mt-1 block w-full"
                        :value="old('email', $user->email)"
                        required
                    />
                    <x-shared::input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Roles -->
                <div>
                    <x-shared::input-label for="roles">
                        {{ __('admin::auth.users.roles_header') }}
                    </x-shared::input-label>
                    <div class="mt-2">
                        <x-shared::searchable-multi-select
                            name="roles[]"
                            :options="$roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'description' => $r->description])->toArray()"
                            :selected="old('roles', $user->roles->pluck('id')->map(fn($id) => (string) $id)->toArray())"
                            valueField="id"
                            color="primary"
                        />
                    </div>
                    <x-shared::input-error :messages="$errors->get('roles')" class="mt-1" />
                </div>

                <!-- User Info (read-only) -->
                <div class="surface-read p-4 rounded-lg">
                    <h3 class="font-medium text-lg mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">info</span>
                        {{ __('auth::admin.users.form.info_section') }}
                    </h3>

                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-fg/60">{{ __('admin::auth.users.is_active_header') }}</dt>
                            <dd class="font-medium">
                                @if ($user->is_active)
                                    <span class="text-success">{{ __('admin::auth.users.status.active') }}</span>
                                @else
                                    <span class="text-error">{{ __('admin::auth.users.status.inactive') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-fg/60">{{ __('admin::auth.users.email_verified_at_header') }}</dt>
                            <dd class="font-medium">
                                @if ($user->email_verified_at)
                                    <span class="text-success">{{ $user->email_verified_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="text-error">{{ __('auth::admin.users.form.not_verified') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-fg/60">{{ __('auth::admin.users.table.terms_accepted') }}</dt>
                            <dd class="font-medium">
                                @if ($user->terms_accepted_at)
                                    {{ $user->terms_accepted_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-fg/50">-</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-fg/60">{{ __('admin::shared.column.created_at') }}</dt>
                            <dd class="font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>

                    @if ($user->is_under_15)
                        <div class="mt-4 pt-4 border-t border-border">
                            <h4 class="font-medium flex items-center gap-2 text-warning mb-2">
                                <span class="material-symbols-outlined">child_care</span>
                                {{ __('auth::admin.users.form.minor_section') }}
                            </h4>
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-fg/60">{{ __('auth::admin.users.table.authorization_verified') }}</dt>
                                    <dd class="font-medium">
                                        @if ($user->parental_authorization_verified_at)
                                            <span class="text-success">{{ $user->parental_authorization_verified_at->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="text-warning">{{ __('auth::admin.users.form.pending') }}</span>
                                        @endif
                                    </dd>
                                </div>
                                @if ($user->parental_authorization_verified_at)
                                    <div>
                                        <dt class="text-fg/60">{{ __('auth::admin.users.form.authorization_file') }}</dt>
                                        <dd class="flex items-center gap-3">
                                            <a href="{{ route('auth.admin.users.download-authorization', $user) }}" 
                                               class="inline-flex items-center gap-1 text-primary hover:text-primary/80">
                                                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                                {{ __('auth::admin.users.table.download_authorization') }}
                                            </a>
                                        </dd>
                                    </div>
                                    <div class="col-span-2" x-data="clearAuthorizationComponent()">
                                        <x-shared::button type="button" color="warning" :outline="true" size="sm" ::disabled="loading" x-on:click="clearAuthorization">
                                            <span class="material-symbols-outlined text-[18px]" x-show="!loading">delete</span>
                                            <span class="material-symbols-outlined text-[18px] animate-spin" x-show="loading" x-cloak>progress_activity</span>
                                            {{ __('auth::admin.users.authorization.clear_button') }}
                                        </x-shared::button>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </div>

                <!-- Submit -->
                <div class="flex gap-4">
                    <x-shared::button type="submit" color="primary" icon="save">
                        {{ __('auth::admin.users.form.update') }}
                    </x-shared::button>
                    <a href="{{ route('auth.admin.users.index') }}">
                        <x-shared::button type="button" color="secondary">
                            {{ __('auth::admin.users.form.cancel') }}
                        </x-shared::button>
                    </a>
                </div>
            </div>
        </form>
    </div>
</x-admin::layout>

<script>
function clearAuthorizationComponent() {
    return {
        loading: false,
        clearAuthorization() {
            if (this.loading) return;
            if (!confirm({!! json_encode(__('auth::admin.users.authorization.clear_confirm')) !!})) return;
            
            this.loading = true;
            fetch('{{ route('auth.admin.users.clear-authorization', $user) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert({!! json_encode(__('auth::admin.users.authorization.cannot_clear')) !!});
                    this.loading = false;
                }
            })
            .catch(() => {
                alert({!! json_encode(__('auth::admin.users.authorization.cannot_clear')) !!});
                this.loading = false;
            });
        }
    }
}
</script>
