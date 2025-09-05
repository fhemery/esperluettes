            <!-- Settings Dropdown / Guest actions -->
            @if (Auth::user())
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                @if(isset($currentProfile) && $currentProfile)
                                    <img src="{{ $currentProfile->avatar_url }}" alt="avatar"
                                         class="h-6 w-6 rounded-full me-2"/>
                                    <div>{{ $currentProfile->display_name }}</div>
                                @else
                                    <div>{{ Auth::user()->email }}</div>
                                @endif

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if (Auth::user()->hasVerifiedEmail())
                                <x-dropdown-link :href="route('profile.show.own')">
                                    {{ __('shared::navigation.profile') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('account.edit')">
                                    {{ __('shared::navigation.account') }}
                                </x-dropdown-link>
                            @else
                                <x-dropdown-link :href="route('verification.notice')">
                                    {{ __('shared::navigation.verify-email') }}
                                </x-dropdown-link>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                                 onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                    {{ __('shared::navigation.logout') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            @else
                <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                        {{ __('shared::navigation.login') }}
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="btn-primary">
                            {{ __('shared::navigation.register') }}
                        </a>
                    @endif
                </div>
            @endif
