@props(['role', 'storySlug'])

@php
    $roleKey = str_replace('-', '_', $role);
    $roleLabel = __('story::collaborators.roles.' . $roleKey);
    $modalName = 'confirm-leave-story';
@endphp

<div x-data="{ open: false }" class="relative">
    <button 
        type="button" 
        @click="open = !open"
        class="inline-flex items-center gap-1 px-2 py-1 text-sm bg-accent/20 text-accent rounded hover:bg-accent/30 transition-colors"
    >
        <span class="material-symbols-outlined text-sm">badge</span>
        <span>{{ $roleLabel }}</span>
        <span class="material-symbols-outlined text-sm" x-text="open ? 'expand_less' : 'expand_more'"></span>
    </button>

    <div 
        x-show="open" 
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-64 surface-read text-on-surface rounded-lg shadow-lg z-50 p-4"
    >
        <p class="text-sm mb-3">{{ __('story::collaborators.badge_info.' . $roleKey) }}</p>
        
        <x-shared::button 
            type="button" 
            color="error" 
            icon="logout"
            class="w-full"
            x-on:click="$dispatch('open-modal', '{{ $modalName }}')"
        >
            {{ __('story::collaborators.leave_button') }}
        </x-shared::button>
    </div>
</div>

<x-shared::confirm-modal
    :name="$modalName"
    :title="__('story::collaborators.confirm_leave_title')"
    :body="__('story::collaborators.confirm_leave_body')"
    :cancel="__('story::collaborators.cancel')"
    :confirm="__('story::collaborators.confirm')"
    :action="route('stories.collaborators.leave', ['slug' => $storySlug])"
/>
