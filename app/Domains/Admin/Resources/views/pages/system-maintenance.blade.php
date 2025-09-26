@php(/** @var \App\Domains\Auth\Private\Models\User|null $user */ $user = auth()->user())

<x-filament-panels::page>
    <div class="prose dark:prose-invert max-w-none">
        <h1 class="text-2xl font-semibold">{{ __('admin::pages.system_maintenance.title') }}</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('admin::pages.system_maintenance.description') }}</p>
        @if(!$user || !$user->hasRole('tech-admin'))
            <div class="mt-4 text-red-600 dark:text-red-400">
                {{ __('admin::pages.system_maintenance.no_permission') }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
