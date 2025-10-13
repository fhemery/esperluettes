@section('title', __('profile::edit.title', ['name' => $profile->display_name]))
<x-app-layout size="md" :page="$page">
    <!-- Header -->
    <x-shared::title icon="edit">{{ __('profile::edit.title') }}</x-shared::title>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data"
            class="surface-read text-on-surface p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left: Picture + upload/remove controls -->
            <div class="md:col-span-1 flex flex-col gap-4 items-center"
                x-data="{ hasFile: false }">
                <x-shared::title tag="h2" >
                    {{ __('profile::edit.profile_picture.title') }}
                </x-shared::title>

                <x-shared::avatar :src="$profile->profile_picture_path" class="h-32 w-32 rounded-full mx-auto" />

                @if($profile->hasCustomProfilePicture())
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="remove_profile_picture" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                        <span class="ml-2 text-sm">{{ __('profile::edit.profile_picture.remove') }}</span>
                    </label>
                </div>
                @endif

                <x-input-label for="profile_picture" :value="__('profile::edit.profile_picture.upload')" class="text-on-surface" />
                <input type="file"
                    name="profile_picture"
                    id="profile_picture"
                    accept="image/*"
                    @change="hasFile = $event.target.files && $event.target.files.length > 0"
                    class="block w-full text-sm text-gray-700 ring-1 ring-inset ring-gray-200 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    :class="{
                                               'ring-green-300 file:bg-green-50 file:text-green-700 hover:file:bg-green-100': hasFile,
                                               'ring-gray-200': !hasFile
                                           }">
                <p class="mt-1 text-xs text-gray-500">{{ __('profile::edit.profile_picture.upload_hint') }}</p>
                <p x-show="hasFile" x-cloak class="mt-2 text-sm text-green-700" aria-live="polite">
                    {{ __('profile::edit.profile_picture.click_save') }}
                </p>
            </div>

            <!-- Right: Profile form fields -->
            <div class="flex flex-col gap-4 md:col-span-2">
                <!-- Display Name (editable, owned by Profile) -->
                <div class="flex flex-col gap-2">
                    <x-input-label for="display_name" :required="true" :value="__('profile::edit.display_name.label')" class="text-on-surface" />
                    <x-text-input id="display_name" class="w-full" type="text" name="display_name" :value="old('display_name', $profile->display_name)" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('display_name')" class="error-on-surface" />
                    <p class="text-xs text-gray-500">{{ __('profile::edit.display_name.hint') }}</p>
                </div>

                <!-- Description -->
                <div class="flex flex-col gap-2">
                    <x-input-label for="description" :value="__('profile::edit.description.label')" class="text-on-surface" />
                    <x-shared::editor name="description" id="editor" max="1000" nbLines="10" defaultValue="{{ old('description', $profile->description) }}" />
                    <x-input-error :messages="$errors->get('description')" class="error-on-surface" />
                </div>

                <!-- Social Networks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-shared::title tag="h2" class="md:col-span-2">
                        {{ __('profile::edit.networks.title') }}
                    </x-shared::title>

                        <!-- Facebook -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                <x-input-label for="facebook_url" :value="__('profile::edit.networks.facebook')" class="text-on-surface" />
                            </div>
                            <x-text-input id="facebook_url" class="w-full" type="url" name="facebook_url" :value="old('facebook_url', $profile->facebook_url)" autocomplete="facebook_url" />
                            <x-input-error :messages="$errors->get('facebook_url')" class="error-on-surface" />
                        </div>

                        <!-- X (Twitter) -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                                <x-input-label for="x_url" :value="__('profile::edit.networks.x')" class="text-on-surface" />
                            </div>
                            <x-text-input id="x_url" class="w-full" type="url" name="x_url" :value="old('x_url', $profile->x_url)" autocomplete="x_url" />
                            <x-input-error :messages="$errors->get('x_url')" class="error-on-surface" />
                        </div>

                        <!-- Instagram -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987c6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447c0-1.297.49-2.448 1.297-3.323.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323c0 1.297-.49 2.448-1.297 3.323-.875.807-2.026 1.297-3.323 1.297zm7.83-9.404h-1.297V6.287h1.297v1.297zm-1.297 1.297h1.297v1.297h-1.297V8.881z" />
                                </svg>
                                <x-input-label for="instagram_url" :value="__('profile::edit.networks.instagram')" class="text-on-surface" />
                            </div>
                            <x-text-input id="instagram_url" class="w-full" type="url" name="instagram_url" :value="old('instagram_url', $profile->instagram_url)" autocomplete="instagram_url" />
                            <x-input-error :messages="$errors->get('instagram_url')" class="error-on-surface" />
                        </div>

                        <!-- YouTube -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                </svg>
                                <x-input-label for="youtube_url" :value="__('profile::edit.networks.youtube')" class="text-on-surface" />
                            </div>
                            <x-text-input id="youtube_url" class="w-full" type="url" name="youtube_url" :value="old('youtube_url', $profile->youtube_url)" autocomplete="youtube_url" />
                            <x-input-error :messages="$errors->get('youtube_url')" class="error-on-surface" />
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-3 flex justify-end gap-4">
                    <a href="{{ route('profile.show.own') }}">
                        <x-shared::button type="button" color="neutral" :outline="true">
                            {{ __('profile::edit.cancel') }}
                        </x-shared::button>
                    </a>
                    <x-shared::button type="submit" color="accent">
                        {{ __('profile::edit.submit') }}
                    </x-shared::button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>