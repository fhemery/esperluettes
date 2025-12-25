<x-app-layout :page="$page">
    <div class="max-w-4xl mx-auto p-4 flex flex-col gap-6">
        <x-shared::title>{{ __('story::collaborators.page_title', ['title' => $story->title]) }}</x-shared::title>

        <div class="surface-read text-on-surface p-4">
            <x-shared::title tag="h2">{{ __('story::collaborators.list_title') }}</x-shared::title>

            <div class="flex flex-col gap-2">
                @foreach ($collaborators as $collab)
                    <div class="flex items-center justify-between p-3 bg-bg rounded-lg">
                        <div class="flex items-center gap-3">
                            <x-shared::avatar :src="$collab['avatar_url']" :alt="$collab['display_name']" class="h-10 w-10" />
                            <div>
                                <a href="{{ route('profile.show', ['profile' => $collab['slug']]) }}" class="font-medium hover:text-accent">
                                    {{ $collab['display_name'] }}
                                </a>
                                <div class="text-sm text-gray-500">
                                    {{ __('story::collaborators.roles.' . str_replace('-', '_', $collab['role'])) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($collab['user_id'] === $currentUserId && $collab['role'] === 'author' && $canLeave)
                                <form action="{{ route('stories.collaborators.leave', ['slug' => $story->slug]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="button" 
                                        x-data
                                        x-on:click="$dispatch('open-modal', 'confirm-leave-{{ $collab['user_id'] }}')"
                                        class="text-warning hover:text-warning/80" 
                                        title="{{ __('story::collaborators.leave_tooltip') }}">
                                        <span class="material-symbols-outlined">logout</span>
                                    </button>
                                </form>
                                <x-shared::confirm-modal 
                                    name="confirm-leave-{{ $collab['user_id'] }}"
                                    :title="__('story::collaborators.confirm_leave_title')"
                                    :body="__('story::collaborators.confirm_leave_body')"
                                    :cancel="__('story::collaborators.cancel')"
                                    :confirm="__('story::collaborators.confirm_leave')"
                                    :action="route('stories.collaborators.leave', ['slug' => $story->slug])"
                                    method="POST"
                                />
                            @elseif ($collab['role'] !== 'author')
                                <button type="button"
                                    x-data
                                    x-on:click="$dispatch('open-modal', 'confirm-remove-{{ $collab['user_id'] }}')"
                                    class="text-error hover:text-error/80"
                                    title="{{ __('story::collaborators.remove_tooltip') }}">
                                    <span class="material-symbols-outlined">person_remove</span>
                                </button>
                                <x-shared::confirm-modal 
                                    name="confirm-remove-{{ $collab['user_id'] }}"
                                    :title="__('story::collaborators.confirm_remove_title')"
                                    :body="__('story::collaborators.confirm_remove_body', ['name' => $collab['display_name']])"
                                    :cancel="__('story::collaborators.cancel')"
                                    :confirm="__('story::collaborators.confirm_remove')"
                                    :action="route('stories.collaborators.destroy', ['slug' => $story->slug, 'targetUserId' => $collab['user_id']])"
                                    method="DELETE"
                                />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-4 surface-read text-on-surface">
            <x-shared::title tag="h2">{{ __('story::collaborators.add_title') }}</x-shared::title>

            <form action="{{ route('stories.collaborators.store', ['slug' => $story->slug]) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <x-shared::input-label for="profile">{{ __('story::collaborators.search_label') }}</x-shared::input-label>
                    <x-profile::profile-and-role-picker :profiles-only="true" />
                </div>

                <div class="flex flex-col md:flex-row gap-4">
                    <div>
                        <x-shared::input-label for="role">{{ __('story::collaborators.role_label') }}</x-shared::input-label>
                        <select name="role" id="role" class="w-full md:w-auto border border-accent px-3 py-2 focus:ring-2 focus:ring-accent/40 focus:border-accent" x-data x-ref="roleSelect">
                            @foreach ($roles as $roleSlug => $roleLabel)
                                <option value="{{ $roleSlug }}">{{ __($roleLabel) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>{{ __('story::collaborators.role_explanation') }}</div>
                </div>

                <div x-data="{ showConfirm: false, role: 'beta-reader' }" x-init="$watch('$refs.roleSelect?.value', v => role = v || 'beta-reader')">
                    <template x-if="role === 'author'">
                        <div>
                            <x-shared::button color="accent" type="button" @click="showConfirm = true">
                                {{ __('story::collaborators.add_button') }}
                            </x-shared::button>
                            <x-shared::confirm-modal 
                                name="confirm-add-author"
                                :title="__('story::collaborators.confirm_author_title')"
                                :body="__('story::collaborators.confirm_author_body')"
                                :cancel="__('story::collaborators.cancel')"
                                :confirm="__('story::collaborators.confirm')"
                                x-show="showConfirm"
                                @close="showConfirm = false"
                            />
                        </div>
                    </template>
                    <template x-if="role !== 'author'">
                        <x-shared::button color="accent" type="submit">
                            {{ __('story::collaborators.add_button') }}
                        </x-shared::button>
                    </template>
                </div>
            </form>
        </div>
        
        <div class="flex justify-center">
            <a href="{{ route('stories.show', ['slug' => $story->slug]) }}">
                <x-shared::button color="neutral" :outline="true">
                    {{ __('story::collaborators.back_to_story') }}
                </x-shared::button>
            </a>
        </div>
    </div>
</x-app-layout>
