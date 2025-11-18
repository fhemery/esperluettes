@inject('registry', 'App\Domains\Administration\Public\Contracts\AdminNavigationRegistry')

@php
    $navigation = $registry->getNavigation();
    $currentUrl = request()->url();
@endphp

<div class="p-4 flex flex-col gap-8">
    @foreach ($navigation as $groupKey => $group)
        <div>

            <x-shared::collapsible :title="$group['label']" :open="true" color="neutral" textColor="fg">
                <!-- Group pages -->
                <div class="flex flex-col gap-2">
                    @foreach ($group['pages'] as $page)
                        @php
                            $isActive = $page['url'] === $currentUrl;
                        @endphp

                        <a href="{{ $page['url'] }}"
                            class="group flex items-center px-3
                              {{ $isActive
                                  ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                                  : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}">

                            <!-- Icon -->
                            <span class="flex-shrink-0 w-5 mr-2">
                                @if ($page['icon'])
                                    <span class="material-symbols-outlined text-lg">
                                        {{ $page['icon'] }}
                                    </span>
                                @endif
                            </span>

                            <!-- Label -->
                            <span class="flex-1">{{ $page['label'] }}</span>
                        </a>
                    @endforeach
                </div>

            </x-shared::collapsible>

        </div>
    @endforeach

    @if (empty($navigation))
        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
            No admin pages available
        </div>
    @endif
</div>
