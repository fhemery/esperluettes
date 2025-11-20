@inject('registry', 'App\Domains\Administration\Public\Contracts\AdminNavigationRegistry')

@php
    $navigation = $registry->getNavigation();
    $currentUrl = request()->url();
@endphp

<div class="p-4 flex flex-col gap-4">
    <x-administration::navigation-item
        href="{{route('administration.dashboard')}}"
        label="{{ __('administration::dashboard.title') }}"
        icon="dashboard"
        active="{{ $currentUrl === route('administration.dashboard') }}"
        class="text-md"
    />
    <x-administration::navigation-item
        href="{{route('dashboard')}}"
        label="{{ __('administration::navigation.back-to-site') }}"
        icon="undo"
        active="{{ $currentUrl === route('dashboard') }}"
        class="text-md"
    />
    @foreach ($navigation as $groupKey => $group)
        <div>
            <x-shared::collapsible :title="$group['label']" :open="true" 
                color="transparent" textColor="fg" containerClasses="py-0 sm:py-0"
                headerClasses="font-bold">
                <!-- Group pages -->
                <div class="flex flex-col gap-2">
                    @foreach ($group['pages'] as $page)
                        @php
                            $pageUrl = $page['target']->getTargetUrl();
                            $isActive = $pageUrl === $currentUrl;
                        @endphp

                        <x-administration::navigation-item
                            :href="$pageUrl"
                            :label="$page['label']"
                            :icon="$page['icon']"
                            :active="$isActive"
                        />
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
