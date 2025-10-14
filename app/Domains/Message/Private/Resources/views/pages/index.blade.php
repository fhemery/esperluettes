<x-app-layout>
    <div class="flex flex-col gap-6 h-[calc(100vh-150px)]">
        <div class="flex justify-between items-center">
            <x-shared::title icon="mail">{{ __('message::messages.title') }}</x-shared::title>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('messages.compose') }}">
                <x-shared::button color="accent">
                    {{ __('message::messages.compose') }}
                </x-shared::button>
            </a>
            @endif
        </div>

        <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Messages List --}}
            <div class="lg:col-span-1 overflow-y-auto min-h-0">
                <div class="surface-read text-on-surface">
                    @forelse($deliveries as $delivery)
                    <a
                        href="{{ route('messages.show', $delivery) }}"
                        class="block border-b last:border-b-0 p-4 hover:bg-gray-50 transition
                                   {{ $delivery->is_read ? '' : 'bg-blue-50 font-semibold' }}
                                   {{ $selectedDelivery && $selectedDelivery->id === $delivery->id ? 'bg-gray-100' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-4">
                                    <h3 class="text-base truncate">{{ $delivery->message->title }}</h3>
                                    @if(!$delivery->is_read)
                                    <span class="inline-block w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $delivery->message->sent_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="p-8 text-center text-fg/80">
                        {{ __('message::messages.no_messages') }}
                    </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($deliveries->hasPages())
                <div class="mt-4">
                    {{ $deliveries->links() }}
                </div>
                @endif
            </div>

            {{-- Message Detail --}}
            <div class="lg:col-span-2 overflow-y-auto min-h-0 surface-read text-on-surface">
                @if($selectedDelivery)
                <div class="h-full flex flex-col p-4 lg:p-8 gap-6">
                    <div class="border-b border-accent pb-4">
                        <x-shared::title>{{ $selectedDelivery->message->title }}</x-shared::title>
                        <p class="text-sm text-fg/80">
                            {{ __('message::messages.sent_at') }}: {{ $selectedDelivery->message->sent_at->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <div class="flex-1 prose max-w-none">
                        {!! $selectedDelivery->message->content !!}
                    </div>

                    <div class="flex gap-2 pt-4 border-t">
                        <form x-data method="POST" action="{{ route('messages.destroy', $selectedDelivery) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                {{ __('message::messages.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="h-full flex flex-col justify-center items-center surface-read text-on-surface p-12 text-center text-fg/80">
                    {{ __('message::messages.select_message') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>