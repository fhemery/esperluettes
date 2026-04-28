@inject('factory',         \App\Domains\Notification\Public\Services\NotificationFactory::class)
@inject('channelRegistry', \App\Domains\Notification\Public\Services\NotificationChannelRegistry::class)
@inject('prefsService',    \App\Domains\Notification\Private\Services\NotificationPreferencesService::class)

@php
    $groups      = $factory->getGroups();
    $channels    = $channelRegistry->getActiveChannels();
    $preferences = $prefsService->getPreferencesForUser(auth()->id());
@endphp

<form method="POST" action="{{ route('notification.preferences.save') }}" class="p-6">
    @csrf

    <p class="mb-6 text-sm text-fg/60">{{ __('notifications::settings.info_future_only') }}</p>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-fg/10">
                    <th class="text-left py-2 pr-4 font-normal text-fg/50 w-full"></th>
                    <th class="text-center py-2 px-4 min-w-32 font-medium">
                        {{ __('notifications::settings.channel_website') }}
                    </th>
                    @foreach($channels as $channel)
                    <th class="text-center py-2 px-4 min-w-32 font-medium">
                        {{ __($channel->nameTranslationKey) }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group)
                    @php $typesInGroup = $factory->getTypesForGroup($group->id); @endphp
                    @if(count($typesInGroup) > 0)

                    <tr class="border-b border-fg/10 bg-fg/3">
                        <td class="py-2 pr-4 font-semibold text-fg/80" colspan="{{ 1 + 1 + count($channels) }}">
                            {{ __($group->translationKey) }}
                        </td>
                    </tr>

                    @foreach($typesInGroup as $typeDef)
                    <tr class="border-b border-fg/5 hover:bg-fg/2">
                        <td class="py-2 pr-4 text-fg/80">{{ __($typeDef->nameKey) }}</td>

                        {{-- Website toggle --}}
                        <td class="text-center py-2 px-4">
                            @if($typeDef->forcedOnWebsite)
                                <x-shared::toggle name="prefs[{{ $typeDef->type }}][website]" :checked="true" :disabled="true" />
                            @else
                                <input type="hidden" name="prefs[{{ $typeDef->type }}][website]" value="0">
                                <x-shared::toggle name="prefs[{{ $typeDef->type }}][website]" :checked="$preferences[$typeDef->type]['website']['enabled'] ?? true" />
                            @endif
                        </td>

                        {{-- External channel toggles --}}
                        @foreach($channels as $channel)
                        <td class="text-center py-2 px-4">
                            <input type="hidden" name="prefs[{{ $typeDef->type }}][{{ $channel->id }}]" value="0">
                            <x-shared::toggle name="prefs[{{ $typeDef->type }}][{{ $channel->id }}]" :checked="$preferences[$typeDef->type][$channel->id]['enabled'] ?? false" />
                        </td>
                        @endforeach
                    </tr>
                    @endforeach

                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-4 py-2 rounded bg-primary text-on-primary hover:opacity-80">
            {{ __('notifications::settings.save') }}
        </button>
    </div>
</form>
