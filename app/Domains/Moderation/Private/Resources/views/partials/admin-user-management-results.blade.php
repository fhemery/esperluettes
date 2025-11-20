@if ($message)
    <div data-role="user-search-message" class="text-sm text-gray-600">
        {{ $message }}
    </div>
@elseif (! empty($users))
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.user_id') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.profile_name') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.email') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.status') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.confirmed_reports') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.rejected_reports') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('moderation::admin.user_management.headers.actions') }}</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $user['id'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $user['display_name'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $user['email'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">
                        {{ $user['is_active'] ? __('moderation::admin.user_management.status.active') : __('moderation::admin.user_management.status.inactive') }}
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $user['confirmed'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $user['rejected'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 space-x-2">
                        <button type="button" class="px-2 py-1 text-xs border border-gray-300 rounded" x-on:click="navigator.clipboard.writeText('{{ $user['email'] }}')">
                            {{ __('moderation::admin.user_management.actions.copy_email') }}
                        </button>
                        @if ($user['is_active'])
                            <button type="button" class="px-2 py-1 text-xs text-red-700 border border-red-300 rounded" x-on:click="deactivateUser('{{ route('auth.admin.users.deactivate', ['user' => $user['id']]) }}')">
                                {{ __('moderation::admin.user_management.actions.deactivate') }}
                            </button>
                        @else
                            <button type="button" class="px-2 py-1 text-xs text-green-700 border border-green-300 rounded" x-on:click="activateUser('{{ route('auth.admin.users.reactivate', ['user' => $user['id']]) }}')">
                                {{ __('moderation::admin.user_management.actions.reactivate') }}
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
