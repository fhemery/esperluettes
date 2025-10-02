<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold">{{ __('message::messages.title') }}</h1>
            @if(Auth::user()->isAdmin())
                <a href="{{ route('messages.compose') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    {{ __('message::messages.compose') }}
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Messages List --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border">
                    @forelse($deliveries as $delivery)
                        <a 
                            href="{{ route('messages.show', $delivery) }}" 
                            class="block border-b last:border-b-0 p-4 hover:bg-gray-50 transition
                                   {{ $delivery->is_read ? '' : 'bg-blue-50 font-semibold' }}
                                   {{ $selectedDelivery && $selectedDelivery->id === $delivery->id ? 'bg-gray-100' : '' }}"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    @if(!$delivery->is_read)
                                        <span class="inline-block w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    @endif
                                    <h3 class="text-base truncate">{{ $delivery->message->title }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $delivery->message->sent_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center text-gray-500">
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
            <div class="lg:col-span-2">
                @if($selectedDelivery)
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <div class="border-b pb-4 mb-4">
                            <h2 class="text-2xl font-semibold">{{ $selectedDelivery->message->title }}</h2>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ __('message::messages.sent_at') }}: {{ $selectedDelivery->message->sent_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        <div class="prose max-w-none mb-6">
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
                    <div class="bg-white rounded-lg shadow-sm border p-12 text-center text-gray-500">
                        {{ __('message::messages.select_message') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
