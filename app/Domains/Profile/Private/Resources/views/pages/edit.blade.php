@section('title', __('profile::edit.title', ['name' => $profile->display_name]))
<x-app-layout size="lg" :page="$page">
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
                    accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                    @change="hasFile = window.ensureMaxFileSize($event, 2); if(!hasFile){ $event.target.value=''; }"
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
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                <x-input-label for="facebook_handle" :value="__('profile::edit.networks.facebook')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">facebook.com/</span>
                                <input type="text" id="facebook_handle" name="facebook_handle"
                                    value="{{ old('facebook_handle', $profile->facebook_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('facebook_handle')" class="error-on-surface" />
                        </div>

                        <!-- X (Twitter) -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                                <x-input-label for="x_handle" :value="__('profile::edit.networks.x')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">x.com/</span>
                                <input type="text" id="x_handle" name="x_handle"
                                    value="{{ old('x_handle', $profile->x_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('x_handle')" class="error-on-surface" />
                        </div>

                        <!-- Instagram -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987c6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447c0-1.297.49-2.448 1.297-3.323.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323c0 1.297-.49 2.448-1.297 3.323-.875.807-2.026 1.297-3.323 1.297zm7.83-9.404h-1.297V6.287h1.297v1.297zm-1.297 1.297h1.297v1.297h-1.297V8.881z" />
                                </svg>
                                <x-input-label for="instagram_handle" :value="__('profile::edit.networks.instagram')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">instagram.com/</span>
                                <input type="text" id="instagram_handle" name="instagram_handle"
                                    value="{{ old('instagram_handle', $profile->instagram_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('instagram_handle')" class="error-on-surface" />
                        </div>

                        <!-- YouTube -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                </svg>
                                <x-input-label for="youtube_handle" :value="__('profile::edit.networks.youtube')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">youtube.com/</span>
                                <input type="text" id="youtube_handle" name="youtube_handle"
                                    value="{{ old('youtube_handle', $profile->youtube_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('youtube_handle')" class="error-on-surface" />
                        </div>

                        <!-- TikTok -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.01a8.16 8.16 0 0 0 4.77 1.52V7.08a4.85 4.85 0 0 1-1-.39z" />
                                </svg>
                                <x-input-label for="tiktok_handle" :value="__('profile::edit.networks.tiktok')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">tiktok.com/@</span>
                                <input type="text" id="tiktok_handle" name="tiktok_handle"
                                    value="{{ old('tiktok_handle', $profile->tiktok_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('tiktok_handle')" class="error-on-surface" />
                        </div>

                        <!-- Bluesky -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 0 1-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.204-.659-.298-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8z" />
                                </svg>
                                <x-input-label for="bluesky_handle" :value="__('profile::edit.networks.bluesky')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">bsky.app/profile/</span>
                                <input type="text" id="bluesky_handle" name="bluesky_handle"
                                    value="{{ old('bluesky_handle', $profile->bluesky_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('bluesky_handle')" class="error-on-surface" />
                        </div>

                        <!-- Mastodon -->
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-center">
                                <svg class="w-4 h-4 inline mr-1 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 0 0 .023-.043v-1.809a.052.052 0 0 0-.02-.041.053.053 0 0 0-.046-.01 20.282 20.282 0 0 1-4.709.545c-2.73 0-3.463-1.284-3.674-1.818a5.593 5.593 0 0 1-.319-1.433.053.053 0 0 1 .066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.125-.01 1.57-.044 3.224-.124 4.768-.422.038-.008.077-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.008-.145.03-1.52.03-1.67.002-.512.167-3.63-.024-5.545zm-3.748 9.195h-2.561V8.29c0-1.309-.55-1.976-1.67-1.976-1.23 0-1.846.79-1.846 2.35v3.403h-2.546V8.663c0-1.56-.617-2.35-1.848-2.35-1.112 0-1.668.668-1.67 1.977v6.218H4.822V8.102c0-1.31.337-2.35 1.011-3.12.696-.77 1.608-1.164 2.74-1.164 1.311 0 2.302.5 2.962 1.498l.638 1.06.638-1.06c.66-.999 1.65-1.498 2.96-1.498 1.13 0 2.043.395 2.74 1.164.675.77 1.012 1.81 1.012 3.12z" />
                                </svg>
                                <x-input-label for="mastodon_handle" :value="__('profile::edit.networks.mastodon')" class="text-on-surface" />
                            </div>
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500">
                                <span class="flex select-none items-center pl-3 text-gray-400 sm:text-sm no-wrap">@</span>
                                <input type="text" id="mastodon_handle" name="mastodon_handle"
                                    placeholder="user@instance.social"
                                    value="{{ old('mastodon_handle', $profile->mastodon_handle) }}"
                                    class="flex-1 border-0 bg-transparent py-1.5 pl-1 pr-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 outline-hidden" />
                            </div>
                            <x-input-error :messages="$errors->get('mastodon_handle')" class="error-on-surface" />
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
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
    @push('scripts')
    <script>
      window.ensureMaxFileSize = function(e, maxMB){
        const f = e.target.files && e.target.files[0];
        if(!f) return false;
        if(f.size > maxMB * 1024 * 1024){
          alert('{{ __("profile::edit.errors.profile_picture_too_large") }}');
          return false;
        }
        return true;
      }
    </script>
    @endpush
</x-app-layout>