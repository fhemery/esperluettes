<!-- Navigation Links -->
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <x-nav-link :href="route('news.index')" :active="request()->routeIs('news.index')">
        {{ __('shared::navigation.news') }}
    </x-nav-link>
    <x-nav-link :href="route('stories.index')" :active="request()->routeIs('stories.index')">
        {{ __('shared::navigation.stories') }}
    </x-nav-link>
    @if (Auth::user() && Auth::user()->hasVerifiedEmail())
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('shared::navigation.dashboard') }}
        </x-nav-link>
        @if (Auth::user()->isAdmin())
            <x-nav-link :href="route('filament.admin.pages.dashboard')"
                        :active="request()->routeIs('filament.admin.*')">
                {{ __('shared::navigation.admin') }}
            </x-nav-link>
        @endif
    @endif
</div>
