<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('auth::admin.roles.title') }}</x-shared::title>
            <a href="{{ route('auth.admin.roles.create') }}">
                <x-shared::button color="primary" icon="add">
                    {{ __('auth::admin.roles.create_button') }}
                </x-shared::button>
            </a>
        </div>

        <x-shared::flash-block />

        <div class="surface-read p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('auth::admin.roles.table.id') }}</th>
                        <th class="p-3">{{ __('auth::admin.roles.table.name') }}</th>
                        <th class="p-3">{{ __('auth::admin.roles.table.slug') }}</th>
                        <th class="p-3">{{ __('auth::admin.roles.table.users_count') }}</th>
                        <th class="p-3 text-right">{{ __('auth::admin.roles.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 text-fg/50">{{ $role->id }}</td>
                            <td class="p-3 font-medium">{{ $role->name }}</td>
                            <td class="p-3 font-mono text-sm text-fg/70">{{ $role->slug }}</td>
                            <td class="p-3">{{ $role->users_count }}</td>
                            <td class="p-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('auth.admin.roles.edit', $role) }}">
                                        <x-shared::button color="secondary" size="sm">
                                            {{ __('auth::admin.users.edit_button') }}
                                        </x-shared::button>
                                    </a>
                                    @if ($role->users_count === 0)
                                        <form method="POST" action="{{ route('auth.admin.roles.destroy', $role) }}"
                                              onsubmit="return confirm('{{ __('Confirmer la suppression ?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <x-shared::button type="submit" color="danger" size="sm">
                                                {{ __('auth::admin.users.delete_button') }}
                                            </x-shared::button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-fg/50">
                                {{ __('auth::admin.roles.no_roles') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
