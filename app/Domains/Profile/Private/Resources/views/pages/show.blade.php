@section('title', __('profile::show.title', ['name' => $profile->display_name]))
<x-app-layout>
    <div class="overflow-hidden">
        <!-- Profile Header -->
        <div class="bg-profile-seasonal sm:bg-profile-seasonal-big">
            <div class="px-2 sm:px-8 py-4 sm:py-8 flex items-center gap-2 sm:gap-4">
                <!-- Profile Picture -->
                <div class="flex-shrink-0">
                    <x-shared::avatar :src="$profile->profile_picture_path"
                        class="h-[100px] w-[100px] sm:h-[200px] sm:w-[200px] rounded-full border-4 border-white"
                        alt="{{ __('profile::show.alt_profile_picture', ['name' => $profile->display_name]) }}" />
                </div>

                <!-- User Info -->
                <div class="flex-1 flex flex-col flex-start gap-2">
                    <div class="flex items-center">
                        <x-shared::title class="text-2xl sm:text-4xl text-secondary p-4">{{ $profile->display_name }}</x-shared::title>
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
                                    share
                                </span>
                            </x-shared::badge>
                            <!-- Tooltip -->
                            <div x-show="copied" x-cloak
                                class="absolute left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap text-xs text-white bg-black/60 rounded px-2 py-1 shadow z-50"
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
                        <x-shared::popover placement="bottom">
                            <x-slot name="trigger">
                                <x-shared::badge color="primary" :outline="false" size="md">
                                    {{ $role->name }}
                                </x-shared::badge>
                            </x-slot>
                            {{ $role->description }}
                        </x-shared::popover>
                        @endforeach
                    </div>
                    @endif

                    <div>
                        <x-shared::badge color="neutral" :outline="false">
                            {{ __('profile::show.member_since') }} {{ $profile->created_at->translatedFormat('F Y') }}
                        </x-shared::badge>
                    </div>

                    @if(Auth::check() && !$isOwn)
                    <div class="flex gap-4 justify-end w-full">
                        <x-moderation::report-button 
                            topic-key="profile" 
                            :entity-id="$profile->user_id"
                            :compact="true"
                        />
                        @if($isModerator)
                        <x-moderation::moderation-button
                            badgeColor="warning"
                            position="top"
                            id="profile-moderator-btn"
                        >
                            <x-moderation::action
                                :action="route('profile.moderation.remove-image', $profile->slug)"
                                method="POST"
                                :label="__('profile::moderation.remove_image.label')"
                            />

                            <x-moderation::action
                                :action="route('profile.moderation.empty-about', $profile->slug)"
                                method="POST"
                                :label="__('profile::moderation.empty_about.label')"
                            />

                            <x-moderation::action
                                :action="route('profile.moderation.empty-social', $profile->slug)"
                                method="POST"
                                :label="__('profile::moderation.empty_social.label')"
                            />
                        </x-moderation::moderation-button>
                        @endif
                        
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Profile Content - Route-based tabs -->
        <div class="w-full">
            <div class="lg:col-span-2">
                @php
                    $tabs = [];
                    if (Auth::check()) {
                        $tabs[] = [
                            'key' => 'about',
                            'label' => __('profile::show.about'),
                            'url' => route('profile.show.about', $profile),
                        ];
                    }
                    $tabs[] = [
                        'key' => 'stories',
                        'label' => $isOwn ? __('profile::show.my-stories') : __('profile::show.stories'),
                        'url' => route('profile.show.stories', $profile),
                    ];
                @endphp

                <!-- Tab Navigation -->
                <nav class="surface-primary text-on-surface flex w-full gap-4 text-2xl font-semibold" role="tablist" aria-label="Profile tabs">
                    @foreach($tabs as $tab)
                        <a href="{{ $tab['url'] }}"
                            role="tab"
                            aria-selected="{{ $activeTab === $tab['key'] ? 'true' : 'false' }}"
                            class="flex-1 whitespace-nowrap py-3 px-1 border-b-2 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $activeTab === $tab['key'] ? 'selected border-none font-extrabold' : 'border-transparent' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </nav>

                <!-- Tab Content -->
                <div class="flex flex-col gap-4 p-4 surface-read text-on-surface">
                    @if($activeTab === 'about' && Auth::check())
                        <x-profile::about-panel :profile="$profile" />
                    @elseif($activeTab === 'stories')
                        <x-story::profile-stories-component :user-id="$profile->user_id" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>