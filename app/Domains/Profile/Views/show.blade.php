<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8">
                <div class="flex items-center space-x-6">
                    <!-- Profile Picture -->
                    <div class="flex-shrink-0">
                        <img class="h-24 w-24 rounded-full border-4 border-white shadow-lg"
                             src="{{ $profile->profile_picture_path }}"
                             alt="{{ __('profile::show.alt_profile_picture', ['name' => $profile->display_name]) }}">
                    </div>

                    <!-- User Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-bold text-white">{{ $profile->display_name }}</h1>
                            @if($canEdit)
                                <div x-data="{ url: '{{ route('profile.show', $profile) }}', copied: false }"
                                     class="relative">
                                    <button type="button"
                                            @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 1200) })"
                                            class="inline-flex items-center justify-center h-8 w-8 rounded-full text-white/85 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60 transition"
                                            :title="url"
                                            aria-label="{{ __('profile::show.copy_profile_link') }}">
                                        <!-- Material Symbols link icon -->
                                        <span class="material-symbols-outlined text-[20px] leading-none">
                                        link
                                    </span>
                                    </button>
                                    <!-- Tooltip -->
                                    <div x-show="copied" x-cloak
                                         class="absolute left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap text-xs text-white bg-black/60 rounded px-2 py-1 shadow"
                                         x-transition.opacity.duration.150>
                                        {{ __('profile::show.copied') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                        @if(!empty($profile->roles))
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($profile->roles as $role)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/15 text-white hover:bg-white/25 transition"
                                        title="{{ $role->description }}">
                                    {{ $role->name }}
                                </span>
                                @endforeach
                            </div>
                        @endif
                        <p class="text-blue-100 mt-1">{{ __('profile::show.member_since') }} {{ $profile->created_at->translatedFormat('F Y') }}</p>

                        @if($canEdit)
                            <div class="mt-4">
                                <a href="{{ route('profile.edit') }}"
                                   class="inline-flex items-center px-4 py-2 bg-white text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    {{ __('profile::show.edit_profile') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="px-6 py-8 w-full">
                <!-- Main Content -->
                <div class="lg:col-span-2"
                     x-data="{
                            tab: @json(Auth::check()) ? 'about' : 'stories',
                            storiesLoaded: false,
                            loading: false,
                            async loadStories() {
                                if (this.storiesLoaded) return;
                                this.loading = true;
                                try {
                                    const res = await fetch('/profiles/{{ $profile->slug }}/stories');
                                    const html = await res.text();
                                    this.$refs.stories.innerHTML = html;
                                    // Initialize Alpine on dynamically injected content
                                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                        window.Alpine.initTree(this.$refs.stories);
                                    }
                                    this.storiesLoaded = true;
                                } catch (e) {
                                    this.$refs.stories.innerHTML = '<div class=\'text-sm text-red-600\'>{{ __('profile::show.failed_to_load_stories') }}</div>';
                                } finally {
                                    this.loading = false;
                                }
                            }
                         }"
                     x-init="if (tab === 'stories') loadStories()">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                            @if(Auth::check())
                                <button type="button"
                                        @click="tab='about'"
                                        :class="tab==='about' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                                    {{ __('profile::show.about') }}
                                </button>
                            @endif
                            <button type="button"
                                    @click="tab='stories'; loadStories()"
                                    :class="tab==='stories' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                                {{ $canEdit ? __('profile::show.my-stories') : __('profile::show.stories') }}
                            </button>
                            <!-- Future: stats, comments, etc. -->
                        </nav>
                    </div>

                    <!-- About Panel -->
                    @if(Auth::check())
                        <div x-show="tab==='about'" x-cloak>
                            <x-profile.about-panel :profile="$profile"/>
                        </div>
                    @endif

                    <!-- Stories Panel -->
                    <div x-show="tab==='stories'" x-cloak>
                        <div x-show="loading" class="text-sm text-gray-500">{{ __('profile::show.loading') }}</div>
                        <div x-ref="stories" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
