<x-admin::layout>
    <div class="flex flex-col gap-6">
        <x-shared::title>{{ __('auth::admin.activation_codes.create_title') }}</x-shared::title>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('auth.admin.activation-codes.store') }}" class="flex flex-col gap-6 max-w-lg">
            @csrf

            <div>
                <x-shared::input-label for="sponsor_user_id">
                    {{ __('auth::admin.activation_codes.form.sponsor_user_id') }}
                </x-shared::input-label>
                <x-profile::user-search-input name="sponsor_user_id" />
                <p class="mt-1 text-sm text-fg/50">{{ __('auth::admin.activation_codes.form.sponsor_user_id_helper') }}</p>
                <x-shared::input-error :messages="$errors->get('sponsor_user_id')" />
            </div>

            <div>
                <x-shared::input-label for="comment">
                    {{ __('auth::admin.activation_codes.form.comment') }}
                </x-shared::input-label>
                <textarea
                    id="comment"
                    name="comment"
                    rows="3"
                    class="mt-1 block w-full form-control"
                    placeholder="{{ __('auth::admin.activation_codes.form.comment_placeholder') }}"
                >{{ old('comment') }}</textarea>
                <x-shared::input-error :messages="$errors->get('comment')" />
            </div>

            <div>
                <x-shared::input-label for="expires_at">
                    {{ __('auth::admin.activation_codes.form.expires_at') }}
                </x-shared::input-label>
                <x-shared::text-input
                    type="datetime-local"
                    id="expires_at"
                    name="expires_at"
                    :value="old('expires_at')"
                />
                <p class="mt-1 text-sm text-fg/50">{{ __('auth::admin.activation_codes.form.expires_at_helper') }}</p>
                <x-shared::input-error :messages="$errors->get('expires_at')" />
            </div>

            <div class="flex gap-4">
                <x-shared::button type="submit" color="primary">
                    {{ __('auth::admin.activation_codes.form.save') }}
                </x-shared::button>
                <a href="{{ route('auth.admin.activation-codes.index') }}">
                    <x-shared::button type="button" color="secondary">
                        {{ __('auth::admin.activation_codes.form.cancel') }}
                    </x-shared::button>
                </a>
            </div>
        </form>
    </div>
</x-admin::layout>
