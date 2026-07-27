@section('title', __('profile::show.title', ['name' => $profile->display_name]))
<x-app-layout>
    <div class="overflow-hidden">
        <!-- Profile Header -->
        <div class="bg-profile-seasonal sm:bg-profile-seasonal-big">
            <div class="px-2 sm:px-8 py-4 sm:py-8 flex items-center gap-2 sm:gap-4">
                <!-- Profile Picture -->
                <div class="shrink-0">
                    <x-shared::avatar :src="$profile->profile_picture_path"
                        class="h-[100px] w-[100px] sm:h-[200px] sm:w-[200px] rounded-full border-4 border-white"
                        alt="{{ __('profile::show.alt_profile_picture', ['name' => $profile->display_name]) }}" />
                </div>

                <!-- User Info -->
                <div class="flex-1 flex flex-col flex-start gap-2">
                    <div class="flex items-center gap-4">
                        <x-shared::title class="text-2xl sm:text-4xl text-secondary">{{ $profile->display_name }}</x-shared::title>
                        @if(!$isOwn)
                        <div class="mb-4">
                            <x-follow::follow-button :user-id="$profile->user_id" />
                        </div>
                        @endif
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
                <!-- Tab Navigation -->
                <x-shared::scrollable-tabs :tabs="$tabs" :active-tab="$activeTab" mode="link" />

                <!-- Tab Content -->
                <div class="flex flex-col gap-4 p-4 surface-read text-on-surface">
                    @if($activeTabVisibility)
                    <div class="flex justify-end" data-test-id="profile-tab-visibility">
                        <x-shared::popover placement="bottom">
                            <x-slot name="trigger">
                                <span class="material-symbols-outlined text-xl text-tertiary leading-none">
                                    {{ $activeTabVisibility['hidden'] ? 'visibility_off' : 'visibility' }}
                                </span>
                            </x-slot>
                            <div class="text-sm">
                                <p>{{ $activeTabVisibility['label'] }}</p>
                                <a href="{{ $activeTabVisibility['link_url'] }}" class="underline text-primary">
                                    {{ $activeTabVisibility['link_label'] }}
                                </a>
                            </div>
                        </x-shared::popover>
                    </div>
                    @endif

                    @if($activeTabDefinition)
                        <x-dynamic-component :component="$activeTabDefinition->component"
                            :owner-user-id="$profile->user_id" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>