<x-admin::layout>
    <div class="flex flex-col gap-6">
        <x-shared::title>{{ __('config::admin.feature_toggles.title') }}</x-shared::title>
        @php
            $isTechAdmin = auth()->user()?->hasRole(\App\Domains\Auth\Public\Api\Roles::TECH_ADMIN);
            $accessColors = [
                'on'         => 'bg-green-100 text-green-800',
                'off'        => 'bg-red-100 text-red-800',
                'role_based' => 'bg-yellow-100 text-yellow-800',
            ];
        @endphp

        <x-shared::flash-block />

        <div class="surface-read p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.domain') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.name') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.access') }}</th>
                        <th class="p-3">{{ __('config::admin.feature_toggles.columns.roles') }}</th>
                        <th class="p-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($toggles as $toggle)
                        @php
                            $params = ['domain' => $toggle->domain, 'name' => $toggle->name];
                            $current = $toggle->access->value;
                        @endphp
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 font-mono text-sm">{{ $toggle->domain }}</td>
                            <td class="p-3 font-mono text-sm">{{ $toggle->name }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $accessColors[$current] ?? 'bg-surface-alt text-fg' }}">
                                    {{ __('config::admin.feature_toggles.access.' . $current) }}
                                </span>
                            </td>
                            <td class="p-3 text-sm text-fg/70">
                                {{ !empty($toggle->roles) ? implode(', ', $toggle->roles) : '—' }}
                            </td>
                            <td class="p-3">
                                <div class="flex justify-end flex-wrap gap-1">
                                    @foreach(['on' => 'success', 'off' => 'danger', 'role_based' => 'accent'] as $access => $btnColor)
                                        @if($current !== $access)
                                            <form method="POST" action="{{ route('config.admin.feature-toggles.setAccess', $params) }}">
                                                @csrf
                                                <input type="hidden" name="access" value="{{ $access }}">
                                                <x-shared::button type="submit" color="{{ $btnColor }}" size="sm">
                                                    {{ __('config::admin.feature_toggles.actions.set_' . $access) }}
                                                </x-shared::button>
                                            </form>
                                        @endif
                                    @endforeach

                                    @if($isTechAdmin)
                                        <a href="{{ route('config.admin.feature-toggles.edit', $params) }}">
                                            <x-shared::button color="neutral" size="sm">{{ __('config::admin.feature_toggles.actions.edit') }}</x-shared::button>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-muted">{{ __('config::admin.feature_toggles.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!empty($orphans))
            <x-shared::title tag="h2">{{ __('config::admin.feature_toggles.orphans.title') }}</x-shared::title>

            <div class="surface-read p-4 overflow-x-auto">
                <table class="w-full admin">
                    <thead>
                        <tr class="border-b border-border text-left">
                            <th class="p-3">{{ __('config::admin.feature_toggles.columns.domain') }}</th>
                            <th class="p-3">{{ __('config::admin.feature_toggles.columns.name') }}</th>
                            <th class="p-3">{{ __('config::admin.feature_toggles.columns.access') }}</th>
                            <th class="p-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orphans as $orphan)
                            @php $current = $orphan->access->value; @endphp
                            <tr class="border-b border-border/50 hover:bg-surface-read/50">
                                <td class="p-3 font-mono text-sm">{{ $orphan->domain }}</td>
                                <td class="p-3 text-sm">
                                    <span class="font-mono">{{ $orphan->name }}</span>
                                    <span class="ml-2 px-2 py-0.5 rounded text-xs bg-surface-alt text-fg">{{ __('config::admin.feature_toggles.orphans.label') }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $accessColors[$current] ?? 'bg-surface-alt text-fg' }}">
                                        {{ __('config::admin.feature_toggles.access.' . $current) }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex justify-end">
                                        <form method="POST" action="{{ route('config.admin.feature-toggles.destroy', ['domain' => $orphan->domain, 'name' => $orphan->name]) }}"
                                              onsubmit="return confirm('{{ __('config::admin.feature_toggles.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <x-shared::button type="submit" color="danger" size="sm">{{ __('config::admin.feature_toggles.actions.delete') }}</x-shared::button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-admin::layout>
