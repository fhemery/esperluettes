<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('auth::admin.activation_codes.title') }}</x-shared::title>
            <a href="{{ route('auth.admin.activation-codes.create') }}">
                <x-shared::button color="primary" icon="add">
                    {{ __('auth::admin.activation_codes.create_button') }}
                </x-shared::button>
            </a>
        </div>

        <x-shared::flash-block />

        <form action="{{ route('auth.admin.activation-codes.index') }}" method="GET" class="surface-read p-4 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <x-shared::input-label for="code">{{ __('auth::admin.activation_codes.table.code') }}</x-shared::input-label>
                <x-shared::text-input
                    type="text"
                    id="code"
                    name="code"
                    :value="$filters['code']"
                    placeholder="..."
                />
            </div>
            <div class="w-40">
                <x-shared::input-label for="status">{{ __('auth::admin.activation_codes.table.status') }}</x-shared::input-label>
                <select name="status" id="status" class="mt-1 block w-full form-control">
                    <option value="" @selected($filters['status'] === '')>{{ __('auth::admin.activation_codes.filter.all') }}</option>
                    <option value="active" @selected($filters['status'] === 'active')>{{ __('auth::admin.activation_codes.status.active') }}</option>
                    <option value="used" @selected($filters['status'] === 'used')>{{ __('auth::admin.activation_codes.status.used') }}</option>
                    <option value="expired" @selected($filters['status'] === 'expired')>{{ __('auth::admin.activation_codes.status.expired') }}</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <x-shared::button type="submit" color="primary">
                    {{ __('auth::admin.activation_codes.filter.apply') }}
                </x-shared::button>
                <a href="{{ route('auth.admin.activation-codes.index') }}">
                    <x-shared::button type="button" color="neutral" :outline="true">
                        {{ __('auth::admin.activation_codes.filter.reset') }}
                    </x-shared::button>
                </a>
            </div>
        </form>

        <div class="surface-read p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.code') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.status') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.sponsor') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.used_by') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.used_at') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.expires_at') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.comment') }}</th>
                        <th class="p-3">{{ __('auth::admin.activation_codes.table.created_at') }}</th>
                        <th class="p-3 text-right">{{ __('auth::admin.activation_codes.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($codes as $code)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">
                                <x-shared::copy-button :text="$code->code" class="font-mono text-sm font-medium">
                                    {{ $code->code }}
                                </x-shared::copy-button>
                            </td>
                            <td class="p-3">
                                @if ($code->status === 'active')
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success rounded">
                                        {{ __('auth::admin.activation_codes.status.active') }}
                                    </span>
                                @elseif ($code->status === 'used')
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-error/20 text-error rounded">
                                        {{ __('auth::admin.activation_codes.status.used') }}
                                    </span>
                                @elseif ($code->status === 'expired')
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-warning/20 text-warning rounded">
                                        {{ __('auth::admin.activation_codes.status.expired') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-sm">
                                @if ($code->sponsor_user_id)
                                    {{ $profiles[$code->sponsor_user_id] ?? __('auth::admin.activation_codes.placeholder.deleted') }}
                                @else
                                    <span class="text-fg/40">{{ __('auth::admin.activation_codes.placeholder.no_sponsor') }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-sm">
                                @if ($code->used_by_user_id)
                                    {{ $profiles[$code->used_by_user_id] ?? __('auth::admin.activation_codes.placeholder.deleted') }}
                                @else
                                    <span class="text-fg/40">{{ __('auth::admin.activation_codes.placeholder.not_used') }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-sm">
                                {{ $code->used_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="p-3 text-sm">
                                {{ $code->expires_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="p-3 text-sm text-fg/70">
                                {{ $code->comment ? \Illuminate\Support\Str::limit($code->comment, 50) : '-' }}
                            </td>
                            <td class="p-3 text-sm">
                                {{ $code->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-3 text-right">
                                @if (!$code->isUsed())
                                    <form method="POST" action="{{ route('auth.admin.activation-codes.destroy', $code) }}"
                                          onsubmit="return confirm('{{ __('auth::admin.activation_codes.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <x-shared::button type="submit" color="danger" size="sm">
                                            {{ __('auth::admin.activation_codes.delete_button') }}
                                        </x-shared::button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-fg/50">
                                {{ __('auth::admin.activation_codes.no_codes') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($codes->hasPages())
            <div class="mt-4">
                {{ $codes->links() }}
            </div>
        @endif
    </div>
</x-admin::layout>
