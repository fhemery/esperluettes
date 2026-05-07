<x-admin::layout>
    <div class="flex flex-col gap-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('auth.admin.roles.index') }}" class="text-fg/60 hover:text-fg">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <x-shared::title>{{ __('auth::admin.roles.create_title') }}</x-shared::title>
        </div>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('auth.admin.roles.store') }}" class="surface-read p-6 rounded-lg">
            @csrf
            @include('auth::pages.admin.roles._form')
        </form>
    </div>
</x-admin::layout>
