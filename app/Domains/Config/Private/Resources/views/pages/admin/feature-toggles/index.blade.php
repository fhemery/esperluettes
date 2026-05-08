<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('config::admin.feature_toggles.title') }}</x-shared::title>
            @php $isTechAdmin = auth()->user()?->hasRole(\App\Domains\Auth\Public\Api\Roles::TECH_ADMIN) @endphp
            @if($isTechAdmin)
                <a href="{{ route('config.admin.feature-toggles.create') }}">
                    <x-shared::button color="primary">{{ __('config::admin.feature_toggles.actions.create') }}</x-shared::button>
                </a>
            @endif
        </div>

        <x-shared::flash-block />

        <div class="surface-read p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.domain') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.name') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.description') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.access') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.admin_visibility') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.roles') }}</th>
                        <th class="p-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($toggles as $toggle)
                        @php
                            $descKey = $toggle->domain . '::config.feature_toggles.' . $toggle->name;
                            $description = \Illuminate\Support\Facades\Lang::has($descKey) ? __($descKey) : '';
                            $accessColors = [
                                'on'         => 'bg-green-100 text-green-800',
                                'off'        => 'bg-red-100 text-red-800',
                                'role_based' => 'bg-yellow-100 text-yellow-800',
                            ];
                            $accessColor = $accessColors[$toggle->access] ?? 'bg-surface-alt text-fg';
                        @endphp
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 font-mono text-sm">{{ $toggle->domain }}</td>
                            <td class="p-3 font-mono text-sm">{{ $toggle->name }}</td>
                            <td class="p-3 text-sm text-fg/70 max-w-xs">{{ $description }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $accessColor }}">
                                    {{ __('config::admin.feature_toggles.access.' . $toggle->access) }}
                                </span>
                            </td>
                            <td class="p-3 text-sm">
                                {{ __('config::admin.feature_toggles.admin_visibility.' . $toggle->admin_visibility) }}
                            </td>
                            <td class="p-3 text-sm text-fg/70">
                                {{ !empty($toggle->roles) ? implode(', ', $toggle->roles) : '—' }}
                            </td>
                            <td class="p-3">
                                <div class="flex justify-end flex-wrap gap-1">
                                    @foreach(['on', 'off', 'role_based'] as $access)
                                        @if($toggle->access !== $access)
                                            <form method="POST" action="{{ route('config.admin.feature-toggles.setAccess', $toggle) }}">
                                                @csrf
                                                <input type="hidden" name="access" value="{{ $access }}">
                                                @php
                                                    $btnColors = ['on' => 'success', 'off' => 'danger', 'role_based' => 'accent'];
                                                @endphp
                                                <x-shared::button type="submit" color="{{ $btnColors[$access] }}" size="sm">
                                                    {{ __('config::admin.feature_toggles.actions.set_' . $access) }}
                                                </x-shared::button>
                                            </form>
                                        @endif
                                    @endforeach

                                    @if($isTechAdmin)
                                        <a href="{{ route('config.admin.feature-toggles.edit', $toggle) }}">
                                            <x-shared::button color="neutral" size="sm">{{ __('config::admin.feature_toggles.actions.edit') }}</x-shared::button>
                                        </a>
                                        <form method="POST" action="{{ route('config.admin.feature-toggles.destroy', $toggle) }}"
                                              onsubmit="return confirm('{{ __('config::admin.feature_toggles.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <x-shared::button type="submit" color="danger" size="sm">{{ __('config::admin.feature_toggles.actions.delete') }}</x-shared::button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-muted">{{ __('config::admin.feature_toggles.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
