@section('title', __('profile::show.title', ['name' => $profile->display_name]))
<x-app-layout>
    <div class="overflow-hidden">
        <!-- Profile Header -->
        <div class="bg-profile-seasonal sm:bg-profile-seasonal-big px-2 sm:px-8 py-4 sm:py-8">
            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Profile Picture -->
                <div class="flex-shrink-0">
                    <x-shared::avatar :src="$profile->profile_picture_path"
                        class="h-24 w-24 sm:h-48 sm:w-48 rounded-full border-4 border-white shadow-lg"
                        alt="{{ __('profile::show.alt_profile_picture', ['name' => $profile->display_name]) }}" />
                </div>

                <!-- User Info -->
                <div class="flex-1 flex flex-col flex-start gap-2">
                    <div class="flex items-center">
                        <x-shared::title class="text-2xl sm:text-4xl text-secondary">{{ $profile->display_name }}</x-shared::title>
                    </div>

                    @if($isOwn)
                    <div class="flex flex-wrap gap-4 items-center">
                        <a href="{{ route('profile.edit') }}">
                            <x-shared::badge color="accent">
                                <span class="material-symbols-outlined text-[20px] leading-none" title="{{ __('profile::show.edit_profile') }}">
                                    edit
                                </span>
                            </x-shared::badge>
                        </a>
                        <div x-data="{ url: '{{ route('profile.show', $profile) }}', copied: false }"
                            @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 1200) })"
                            class="relative cursor-pointer">
                            <x-shared::badge color="neutral" outline="true"
                                title="{{ __('profile::show.copy_profile_link') }}"
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
                        <x-shared::badge color="primary" :outline="false" size="md"
                            title="{{ $role->description }}">
                            {{ $role->name }}
                        </x-shared::badge>
                        @endforeach
                    </div>
                    @endif

                    <div>
                        <x-shared::badge color="neutral" :outline="false">
                            {{ __('profile::show.member_since') }} {{ $profile->created_at->translatedFormat('F Y') }}
                        </x-shared::badge>
                    </div>
                </div>
            </div>
        </div>

        @php $initialTab = $isOwn ? 'stories' : 'about'; @endphp
        <!-- Profile Content -->
        <div class="w-full">
            <div class="lg:col-span-2">
                <x-shared::tabs :tabs="[
                        ...(Auth::check() ? [[ 'key' => 'about', 'label' => __('profile::show.about') ]] : []),
                        [ 'key' => 'stories', 'label' => $isOwn ? __('profile::show.my-stories') : __('profile::show.stories') ],
                    ]" :tracking="true" :initial="$initialTab" color="primary" navClass="text-2xl font-semibold">
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
                            <div class="flex flex-col gap-4 p-4 surface-read text-on-surface">
                                <x-profile::about-panel :profile="$profile" />
                            </div>
                        </div>
                        @endif

                        <div x-show="tab==='stories'" x-cloak>
                            <div class="flex flex-col gap-4 p-4 surface-read text-on-surface">
                                <div x-show="loading" class="text-sm text-gray-500">{{ __('profile::show.loading') }}</div>
                                <div x-ref="stories" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </x-shared::tabs>
            </div>
        </div>
    </div>
</x-app-layout>