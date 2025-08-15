<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit Profile') }}</h1>
                    <a href="{{ route('profile.show.own') }}"
                        class="text-gray-600 hover:text-gray-900 transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="p-6">
                @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left: Picture + upload/remove controls -->
                        <div class="lg:col-span-1">
                            <div class="bg-gray-50 rounded-lg p-6" x-data="{ hasFile: false }">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Profile Picture') }}</h2>

                                <div class="text-center">
                                    <img class="h-32 w-32 rounded-full mx-auto border-4 border-white shadow-lg"
                                         src="{{ $profile->profile_picture_url }}"
                                         alt="{{ __('Current profile picture') }}">
                                </div>

                                @if($profile->hasCustomProfilePicture())
                                <div class="mt-6">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="remove_profile_picture" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ __('Remove current picture') }}</span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">{{ __('If you upload a new picture, it will replace the current one even if this is checked.') }}</p>
                                </div>
                                @endif

                                <div class="mt-6">
                                    <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Upload New Picture') }}
                                    </label>
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
                                    <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG, GIF up to 2MB. Min 100x100px.') }}</p>
                                    <p x-show="hasFile" x-cloak class="mt-2 text-sm text-green-700" aria-live="polite">
                                        {{ __('Click save to finalize upload') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Profile form fields -->
                        <div class="lg:col-span-2">
                            <!-- User Name (Read-only) -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Name') }}</label>
                                <input type="text"
                                       value="{{ $user->name }}"
                                       disabled
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                                <p class="mt-1 text-xs text-gray-500">{{ __('Your name cannot be changed from the profile page.') }}</p>
                            </div>

                            <!-- Description with ProseMirror Editor -->
                            <div class="mb-6" x-data="proseMirrorEditor()" x-init="content = '{{ addslashes($profile->description ?? '') }}'" x-on:beforeunload.window="destroy()">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Description') }}
                                </label>

                                <!-- Tiptap Toolbar -->
                                <div class="border border-gray-300 border-b-0 rounded-t-lg bg-gray-50 px-3 py-2 flex items-center space-x-2">
                                    <button type="button"
                                            @click="editor.toggleBold()"
                                            :class="{ 'bg-blue-100 text-blue-700': editor?.isActive('strong') }"
                                            class="p-1.5 rounded hover:bg-gray-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="editor.toggleItalic()"
                                            :class="{ 'bg-blue-100 text-blue-700': editor?.isActive('em') }"
                                            class="p-1.5 rounded hover:bg-gray-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4l4 16m-4-8h8"></path>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="console.log('Strikethrough not implemented in basic schema')"
                                            class="p-1.5 rounded hover:bg-gray-200 transition-colors opacity-50 cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h12M6 8h12m-12 8h12"></path>
                                        </svg>
                                    </button>
                                    <div class="w-px h-6 bg-gray-300"></div>
                                    <button type="button"
                                            @click="editor.toggleBulletList()"
                                            :class="{ 'bg-blue-100 text-blue-700': false }"
                                            class="p-1.5 rounded hover:bg-gray-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="editor.toggleOrderedList()"
                                            :class="{ 'bg-blue-100 text-blue-700': false }"
                                            class="p-1.5 rounded hover:bg-gray-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Tiptap Editor -->
                                <div x-ref="editor" class="min-h-[120px] max-h-[300px] overflow-y-auto"></div>

                                <!-- Hidden input for form submission -->
                                <input type="hidden" name="description" x-ref="hiddenInput" :value="content">

                                <!-- Character count and status -->
                                <div class="mt-1 flex justify-between items-center">
                                    <p class="text-xs text-gray-500">{{ __('Use the toolbar above for formatting. Maximum 1000 characters.') }}</p>
                                    <div class="flex items-center space-x-2">
                                        <span x-show="isOverLimit" class="text-xs text-red-600 font-medium">{{ __('Character limit exceeded!') }}</span>
                                        <span class="text-xs" :class="characterCountClass" x-text="characterCount + '/1000'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Networks -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Social Networks') }}</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Facebook -->
                                    <div>
                                        <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                            </svg>
                                            {{ __('Facebook') }}
                                        </label>
                                        <input type="url"
                                               name="facebook_url"
                                               id="facebook_url"
                                               value="{{ old('facebook_url', $profile->facebook_url) }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="{{ __('Facebook URL placeholder') }}">
                                    </div>

                                    <!-- X (Twitter) -->
                                    <div>
                                        <label for="x_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                            </svg>
                                            {{ __('X (Twitter)') }}
                                        </label>
                                        <input type="url"
                                               name="x_url"
                                               id="x_url"
                                               value="{{ old('x_url', $profile->x_url) }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="{{ __('X URL placeholder') }}">
                                    </div>

                                    <!-- Instagram -->
                                    <div>
                                        <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987c6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447c0-1.297.49-2.448 1.297-3.323.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323c0 1.297-.49 2.448-1.297 3.323-.875.807-2.026 1.297-3.323 1.297zm7.83-9.404h-1.297V6.287h1.297v1.297zm-1.297 1.297h1.297v1.297h-1.297V8.881z" />
                                            </svg>
                                            {{ __('Instagram') }}
                                        </label>
                                        <input type="url"
                                               name="instagram_url"
                                               id="instagram_url"
                                               value="{{ old('instagram_url', $profile->instagram_url) }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="{{ __('Instagram URL placeholder') }}">
                                    </div>

                                    <!-- YouTube -->
                                    <div>
                                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                            </svg>
                                            {{ __('YouTube') }}
                                        </label>
                                        <input type="url"
                                               name="youtube_url"
                                               id="youtube_url"
                                               value="{{ old('youtube_url', $profile->youtube_url) }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="{{ __('YouTube URL placeholder') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                    {{ __('Save Changes') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>