<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8">
                <div class="flex items-center space-x-6">
                    <!-- Profile Picture -->
                    <div class="flex-shrink-0">
                        <img class="h-24 w-24 rounded-full border-4 border-white shadow-lg"
                            src="{{ $profile->profile_picture_url }}"
                            alt="{{ __(":name's profile picture", ['name' => $user->name]) }}">
                    </div>

                    <!-- User Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-bold text-white">{{ $user->name }}</h1>
                            @if($canEdit)
                            <div x-data="{ url: '{{ route('profile.show', $profile) }}', copied: false }" class="relative">
                                <button type="button"
                                        @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 1200) })"
                                        class="inline-flex items-center justify-center h-8 w-8 rounded-full text-white/85 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60 transition"
                                        :title="url"
                                        aria-label="{{ __('Copy profile link') }}">
                                    <!-- Material Symbols link icon -->
                                    <span class="material-symbols-outlined text-[20px] leading-none">
                                        link
                                    </span>
                                </button>
                                <!-- Tooltip -->
                                <div x-show="copied" x-cloak
                                     class="absolute left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap text-xs text-white bg-black/60 rounded px-2 py-1 shadow"
                                     x-transition.opacity.duration.150>
                                    {{ __('Copied!') }}
                                </div>
                            </div>
                            @endif
                        </div>
                        <p class="text-blue-100 mt-1">{{ __('Member since') }} {{ $user->created_at->translatedFormat('F Y') }}</p>

                        @if($canEdit)
                        <div class="mt-4">
                            <a href="{{ route('profile.edit') }}"
                                class="inline-flex items-center px-4 py-2 bg-white text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                {{ __('Edit Profile') }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="px-6 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        @if($profile->description)
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('About') }}</h2>
                            <div class="prose prose-sm max-w-none text-gray-700">
                                {!! $profile->description !!}
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        @if($profile->hasSocialNetworks())
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Connect') }}</h3>
                            <div class="space-y-3">
                                @if($profile->facebook_url)
                                <a href="{{ $profile->facebook_url }}" target="_blank" rel="noopener noreferrer"
                                    class="flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                    </svg>
                                    {{ __('Facebook') }}
                                </a>
                                @endif

                                @if($profile->x_url)
                                <a href="{{ $profile->x_url }}" target="_blank" rel="noopener noreferrer"
                                    class="flex items-center text-gray-900 hover:text-gray-700 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                    </svg>
                                    {{ __('X (Twitter)') }}
                                </a>
                                @endif

                                @if($profile->instagram_url)
                                <a href="{{ $profile->instagram_url }}" target="_blank" rel="noopener noreferrer"
                                    class="flex items-center text-pink-600 hover:text-pink-800 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987c6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447c0-1.297.49-2.448 1.297-3.323.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323c0 1.297-.49 2.448-1.297 3.323-.875.807-2.026 1.297-3.323 1.297zm7.83-9.404h-1.297V6.287h1.297v1.297zm-1.297 1.297h1.297v1.297h-1.297V8.881z" />
                                    </svg>
                                    {{ __('Instagram') }}
                                </a>
                                @endif

                                @if($profile->youtube_url)
                                <a href="{{ $profile->youtube_url }}" target="_blank" rel="noopener noreferrer"
                                    class="flex items-center text-red-600 hover:text-red-800 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                    </svg>
                                    {{ __('YouTube') }}
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>