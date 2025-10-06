<x-app-layout size="sm">
    <div class="w-full flex flex-col gap-8">
        <div class="p-4 sm:p-8 surface-read text-on-surface">
            @include('auth::account.partials.update-account-information-form')
        </div>

        <div class="p-4 sm:p-8 surface-read text-on-surface">
            @include('auth::account.partials.update-password-form')
        </div>

        <div class="p-4 sm:p-8 surface-read text-on-surface">
            @include('auth::account.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>