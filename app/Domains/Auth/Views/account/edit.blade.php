<x-app-layout>
    <div class="max-w-xl">
        <div class="my-2 p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            @include('auth::account.partials.update-account-information-form')
        </div>

        <div class="my-2 p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            @include('auth::account.partials.update-password-form')
        </div>

        <div class="my-2 p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            @include('auth::account.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>