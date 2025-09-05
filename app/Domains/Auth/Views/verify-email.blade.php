<x-app-layout>
    <div class="min-h-screen flex flex-col items-center pt-16">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-4 text-sm text-gray-600">
                {{ __('auth::verify.intro') }}
            </div>

            @if (session('status') == __('verification-link-sent'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ __('auth::verify.link_sent') }}
                </div>
            @endif

            <div class="mt-4 flex items-center justify-center">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <div>
                        <x-shared::button color="accent">
                            {{ __('auth::verify.actions.resend') }}
                        </x-shared::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
