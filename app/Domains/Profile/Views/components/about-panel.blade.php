<div>
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('profile::about.title') }}</h2>
        <div class="prose prose-sm rich-content max-w-none text-gray-700">
            @if($profile->description)
                {!! $profile->description !!}
            @else
                <p class="text-gray-500">{{ __('profile::about.no-bio') }}</p>
            @endif
        </div>
    </div>

    @if($profile->hasSocialNetworks())
        <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('profile::about.networks') }}</h3>
            <div class="space-y-3">
                @if($profile->facebook_url)
                    <a href="{{ $profile->facebook_url }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        {{ __('Facebook') }}
                    </a>
                @endif

                @if($profile->x_url)
                    <a href="{{ $profile->x_url }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center text-gray-900 hover:text-gray-700 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        {{ __('X (Twitter)') }}
                    </a>
                @endif

                @if($profile->instagram_url)
                    <a href="{{ $profile->instagram_url }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center text-pink-600 hover:text-pink-800 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987c6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447c0-1.297.49-2.448 1.297-3.323.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323c0 1.297-.49 2.448-1.297 3.323-.875.807-2.026 1.297-3.323 1.297zm7.83-9.404h-1.297V6.287h1.297v1.297zm-1.297 1.297h1.297v1.297h-1.297V8.881z"/>
                        </svg>
                        {{ __('Instagram') }}
                    </a>
                @endif

                @if($profile->youtube_url)
                    <a href="{{ $profile->youtube_url }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center text-red-600 hover:text-red-800 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        {{ __('YouTube') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
