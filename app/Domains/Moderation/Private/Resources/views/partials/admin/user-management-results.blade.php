@if ($message)
    <div data-role="user-search-message" class="text-sm text-gray-600">
        {{ $message }}
    </div>
@elseif (!empty($users))
    <table class="admin min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th>{{ __('moderation::admin.user_management.headers.user_id') }}</th>
                <th>{{ __('moderation::admin.user_management.headers.profile_name') }}</th>
                <th>{{ __('moderation::admin.user_management.headers.email') }}</th>
                <th>{{ __('moderation::admin.user_management.headers.status') }}</th>
                <th>{{ __('moderation::admin.user_management.headers.confirmed_reports') }}</th>
                <th>{{ __('moderation::admin.user_management.headers.rejected_reports') }}</th>
                <th>{{ __('moderation::admin.user_management.headers.actions') }}</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user['id'] }}</td>
                    <td>{{ $user['display_name'] }}</td>
                    <td class="flex items-center gap-2">
                        <div>{{ $user['email'] }}</div>
                        <div class="relative" x-data="{ copied: false }">
                            <div x-show="!copied">
                            <x-shared::badge 
                                color="neutral" 
                                icon="content_copy"
                                class="cursor-pointer"
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $user['email'] }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                >
                            </x-shared::badge>
                        </div>
                        <div x-show="copied">
                            <x-shared::badge color="success" icon="check" />
                        </div>
                    </td>
                    <td>
                        {{ $user['is_active'] ? __('moderation::admin.user_management.status.active') : __('moderation::admin.user_management.status.inactive') }}
                    </td>
                    <td>{{ $user['confirmed'] }}</td>
                    <td>{{ $user['rejected'] }}</td>
                    <td class="flex gap-2">
                        
                        @if ($user['is_active'])
                            <x-shared::button color="error"
                                x-on:click="deactivateUser('{{ route('auth.admin.users.deactivate', ['user' => $user['id']]) }}')">
                                {{ __('moderation::admin.user_management.actions.deactivate') }}
                            </x-shared::button>
                        @else
                            <x-shared::button color="success"
                                x-on:click="activateUser('{{ route('auth.admin.users.reactivate', ['user' => $user['id']]) }}')">
                                {{ __('moderation::admin.user_management.actions.reactivate') }}
                            </x-shared::button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
