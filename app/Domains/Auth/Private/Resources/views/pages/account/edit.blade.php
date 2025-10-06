<x-app-layout>
    <div class="mx-auto max-w-xl flex flex-col gap-4">
        <div class="my-2 p-4 sm:p-8 surface-read text-on-surface">
            @include('auth::account.partials.update-account-information-form')
        </div>

        <div class="my-2 p-4 sm:p-8 surface-read text-on-surface">
            @include('auth::account.partials.update-password-form')
        </div>

        <div class="my-2 p-4 sm:p-8 surface-read text-on-surface">
            @include('auth::account.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>