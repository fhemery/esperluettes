<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <x-shared::title>{{ __('auth::admin.users.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                <a href="{{ route('auth.admin.users.export') }}">
                    <x-shared::button color="neutral" :outline="true">
                        <span class="material-symbols-outlined text-[18px] leading-none">download</span>
                        {{ __('auth::admin.users.export_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <!-- Filters -->
        <form action="{{ route('auth.admin.users.index') }}" method="GET" class="surface-read p-4 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <x-shared::input-label for="search">{{ __('auth::admin.users.filter.search') }}</x-shared::input-label>
                <x-shared::text-input
                    type="text"
                    id="search"
                    name="search"
                    class="mt-1 block w-full"
                    :value="$filters['search'] ?? ''"
                    placeholder="{{ __('auth::admin.users.filter.search_placeholder') }}"
                />
            </div>
            <div class="w-40">
                <x-shared::input-label for="is_active">{{ __('admin::auth.users.is_active_header') }}</x-shared::input-label>
                <select name="is_active" id="is_active" class="mt-1 block w-full form-control">
                    <option value="">{{ __('auth::admin.users.filter.all') }}</option>
                    <option value="1" @selected(($filters['is_active'] ?? '') === '1')>{{ __('admin::auth.users.status.active') }}</option>
                    <option value="0" @selected(($filters['is_active'] ?? '') === '0')>{{ __('admin::auth.users.status.inactive') }}</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <div>
                    <x-shared::button type="submit" color="primary">
                        {{ __('auth::admin.users.filter.apply') }}
                    </x-shared::button>
                </div>
                <a href="{{ route('auth.admin.users.index') }}">
                    <x-shared::button type="button" color="neutral" :outline="true">
                        {{ __('auth::admin.users.filter.reset') }}
                    </x-shared::button>
                </a>
            </div>
        </form>

        <!-- Users table -->
        <div class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">ID</th>
                        <th class="p-3">{{ __('admin::auth.users.name_header') }}</th>
                        <th class="p-3">{{ __('admin::auth.users.email_header') }}</th>
                        <th class="p-3">{{ __('admin::auth.users.roles_header') }}</th>
                        <th class="p-3">{{ __('admin::auth.users.is_active_header') }}</th>
                        <th class="p-3">{{ __('auth::admin.users.table.status') }}</th>
                        <th class="p-3">{{ __('auth::admin.users.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 font-mono text-xs">{{ $user->id }}</td>
                            <td class="p-3 font-medium">{{ $user->profile_display_name ?? '-' }}</td>
                            <td class="p-3 {{ $user->email_verified_at ? 'text-success' : 'text-error' }}">
                                {{ $user->email }}
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs bg-primary/20 text-primary rounded">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="p-3">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success">
                                        {{ __('admin::auth.users.status.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-error/20 text-error">
                                        {{ __('admin::auth.users.status.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    {{-- Email verification status --}}
                                    @if ($user->email_verified_at)
                                        <span class="material-symbols-outlined text-success" title="{{ __('auth::admin.users.table.email_verified') }}">mark_email_read</span>
                                    @else
                                        <span class="material-symbols-outlined text-error" title="{{ __('auth::admin.users.table.email_not_verified') }}">mark_email_unread</span>
                                    @endif

                                    {{-- Minor status --}}
                                    @if ($user->is_under_15)
                                        @if ($user->parental_authorization_verified_at)
                                            <a href="{{ route('auth.admin.users.download-authorization', $user) }}" 
                                            class="text-primary hover:text-primary/80 relative" >
                                                <span title="{{ __('auth::admin.users.table.download_authorization') }}" class="material-symbols-outlined text-success">child_care</span>
                                                <span title="{{ __('auth::admin.users.table.download_authorization') }}" class="material-symbols-outlined text-[14px] text-success bg-white absolute bottom-0 right-0">picture_as_pdf</span>
                                            </a>
                                        @else
                                            <span class="material-symbols-outlined text-error" title="{{ __('auth::admin.users.table.minor_not_verified') }}">child_care</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 items-center">
                                    <a href="{{ route('auth.admin.users.edit', $user) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('auth::admin.users.edit_button') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    
                                    @if ($user->is_active)
                                        <form action="{{ route('auth.admin.users.deactivate', $user) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('{{ __('admin::auth.users.deactivation.confirm_message') }}')">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-warning hover:text-warning/80"
                                                    title="{{ __('admin::auth.users.actions.deactivate') }}">
                                                <span class="material-symbols-outlined">block</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('auth.admin.users.reactivate', $user) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('{{ __('admin::auth.users.activation.confirm_message') }}')">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-success hover:text-success/80"
                                                    title="{{ __('admin::auth.users.actions.activate') }}">
                                                <span class="material-symbols-outlined">check_circle</span>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('auth.admin.users.destroy', $user) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('{{ __('admin::auth.users.deletion.confirm_message') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-error hover:text-error/80"
                                                title="{{ __('auth::admin.users.delete_button') }}">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>

                                    @if ($user->hasRole(\App\Domains\Auth\Public\Api\Roles::USER))
                                        <form action="{{ route('auth.admin.users.promote', $user) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('{{ __('admin::auth.users.promote.confirm_message', ['from' => 'utilisateur', 'to' => 'utilisateur confirmé']) }}')">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-primary hover:text-primary/80"
                                                    title="{{ __('admin::auth.users.promote.action_label') }}">
                                                <span class="material-symbols-outlined">arrow_upward</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-fg/50">
                                {{ __('auth::admin.users.no_users') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-admin::layout>
