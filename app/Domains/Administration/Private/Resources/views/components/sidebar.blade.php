@inject('registry', 'App\Domains\Administration\Public\Contracts\AdminNavigationRegistry')

@php
    $navigation = $registry->getNavigation();
    $currentUrl = request()->url();
@endphp

<div class="space-y-8">
    @foreach ($navigation as $groupKey => $group)
        <div>
            <!-- Group header -->
            <div class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                {{ $group['label'] }}
            </div>
            
            <!-- Group pages -->
            <div class="mt-3 space-y-1">
                @foreach ($group['pages'] as $page)
                    @php
                        $isActive = $page['url'] === $currentUrl;
                        $isFilament = $page['type'] === 'filament';
                    @endphp
                    
                    <a href="{{ $page['url'] }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                              {{ $isActive 
                                  ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200' 
                                  : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}
                              {{ $isFilament ? ' border-l-2 border-gray-300 dark:border-gray-600' : '' }}">
                        
                        <!-- Icon -->
                        <span class="flex-shrink-0 w-5 h-5 mr-3">
                            @if ($isFilament)
                                <!-- Filament indicator -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            @endif
                            @if ($page['icon'])
                                <span class="material-symbols-rounded text-lg">
                                    {{ $page['icon'] }}
                                </span>
                            @endif
                        </span>
                        
                        <!-- Label -->
                        <span class="flex-1">{{ $page['label'] }}</span>
                        
                        <!-- Filament badge -->
                        @if ($isFilament)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                Filament
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
    
    @if (empty($navigation))
        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
            No admin pages available
        </div>
    @endif
</div>
