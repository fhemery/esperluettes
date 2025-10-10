<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-semibold">{{ __('message::messages.compose_title') }}</h1>
                <a href="{{ route('messages.index') }}" class="text-gray-600 hover:text-gray-800">
                    ← {{ __('message::messages.title') }}
                </a>
            </div>

            <form method="POST" action="{{ route('messages.store') }}" class="bg-white rounded-lg shadow-sm border p-6">
                @csrf

                {{-- Recipients --}}
                <div class="mb-6">
                    <x-input-label :value="__('message::messages.recipients')" />
                    <x-input-error :messages="$errors->get('recipients')" class="mt-2" />
                    
                    <div class="mt-3 space-y-4">
                       <x-profile::profile-and-role-picker
                            :initialUserIds="old('target_users', [])"
                            :initialRoleSlugs="old('target_roles', [])"
                        />
                    </div>
                </div>

                {{-- Title --}}
                <div class="mb-6">
                    <x-input-label for="title" :value="__('message::messages.message_title')" />
                    <x-text-input 
                        id="title" 
                        name="title" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :value="old('title')" 
                        required 
                        maxlength="150" 
                    />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                {{-- Content --}}
                <div class="mb-6">
                    <x-input-label for="content" :value="__('message::messages.message_content')" />
                    <x-editor 
                        name="content" 
                        id="content" 
                        :value="old('content')" 
                        maxlength="1000"
                        class="mt-1"
                    />
                    <x-input-error :messages="$errors->get('content')" class="mt-2" />
                </div>

                {{-- Submit --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('messages.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                        {{ __('shared::actions.cancel') }}
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        {{ __('message::messages.send') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
