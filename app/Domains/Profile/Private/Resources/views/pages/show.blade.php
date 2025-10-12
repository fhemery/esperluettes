@section('title', __('profile::show.title', ['name' => $profile->display_name]))
<x-app-layout>
    <div class="overflow-hidden">
        <!-- Profile Header -->
        <div class="bg-profile-seasonal sm:bg-profile-seasonal-big px-8 py-6">
            <div class="flex items-center space-x-6">
                <!-- Profile Picture -->
                <div class="flex-shrink-0">
                    <x-shared::avatar :src="$profile->profile_picture_path"
                        class="h-24 w-24 sm:h-48 sm:w-48 rounded-full border-4 border-white shadow-lg"
                        alt="{{ __('profile::show.alt_profile_picture', ['name' => $profile->display_name]) }}" />
                </div>

                <!-- User Info -->
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <x-shared::title class="text-2xl sm:text-4xl text-secondary">{{ $profile->display_name }}</x-shared::title>
                    </div>
                    @if($isOwn)
                    <div class="flex gap-4">
                        <div x-data="{ url: '{{ route('profile.show', $profile) }}', copied: false }"
                            @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 1200) })"
                            class="relative cursor-pointer">
                            <x-shared::badge color="neutral" outline="true"
                                x-bind:title="url"
                                aria-label="{{ __('profile::show.copy_profile_link') }}">
                                <!-- Material Symbols link icon -->
                                <span class="material-symbols-outlined text-[20px] leading-none">
                                    link
                                </span>
                            </x-shared::badge>
                            <!-- Tooltip -->
                            <div x-show="copied" x-cloak
                                class="absolute left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap text-xs text-white bg-black/60 rounded px-2 py-1 shadow"
                                x-transition.opacity.duration.150>
                                {{ __('profile::show.copied') }}
                            </div>
                        </div>
                        <x-discord::discord-component />
                    </div>
                    @endif
                    @if(!empty($profile->roles))
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($profile->roles as $role)
                        <x-shared::badge color="primary" :outline="false"
                            title="{{ $role->description }}">
                            {{ $role->name }}
                        </x-shared::badge>
                        @endforeach
                    </div>
                    @endif
                    <p class="text-blue-100 mt-1">{{ __('profile::show.member_since') }} {{ $profile->created_at->translatedFormat('F Y') }}</p>

                    @if($isOwn)
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

        @php $initialTab = $isOwn ? 'stories' : 'about'; @endphp
        <!-- Profile Content -->
        <div class="px-6 py-8 w-full">
            <div class="lg:col-span-2">
                <x-shared::tabs :tabs="[
                        ...(Auth::check() ? [[ 'key' => 'about', 'label' => __('profile::show.about') ]] : []),
                        [ 'key' => 'stories', 'label' => $isOwn ? __('profile::show.my-stories') : __('profile::show.stories') ],
                    ]" :initial="$initialTab">
                    <div x-data="{
                                storiesLoaded: false,
                                loading: false,
                                async loadStories() {
                                    if (this.storiesLoaded) return;
                                    this.loading = true;
                                    try {
                                        const res = await fetch('/profiles/{{ $profile->slug }}/stories');
                                        const html = await res.text();
                                        this.$refs.stories.innerHTML = html;
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
                        x-init="if (tab === 'stories') loadStories()"
                        x-effect="if (tab === 'stories') loadStories()">
                        @if(Auth::check())
                        <div x-show="tab==='about'" x-cloak>
                            <x-profile::about-panel :profile="$profile" />
                        </div>
                        @endif

                        <div x-show="tab==='stories'" x-cloak>
                            <div x-show="loading" class="text-sm text-gray-500">{{ __('profile::show.loading') }}</div>
                            <div x-ref="stories" class="mt-2"></div>
                        </div>
                    </div>
                </x-shared::tabs>
            </div>
        </div>
    </div>
</x-app-layout>