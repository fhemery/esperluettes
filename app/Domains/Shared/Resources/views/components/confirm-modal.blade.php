@props([
    'name',
    'title' => '',
    'body' => '',
    'cancel' => __('Cancel'),
    'confirm' => __('Confirm'),
    'action' => null,
    'method' => 'POST',
    'maxWidth' => 'md',
])

<x-shared::modal :name="$name" :maxWidth="$maxWidth">
    <div class="p-6">
        @if($title)
            <x-shared::title tag="h2">{{ $title }}</x-shared::title>
        @endif
        @if($body)
            <p class="mt-2 text-sm text-gray-600">{{ $body }}</p>
        @else
            {{ $slot }}
        @endif

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="px-4 py-2 rounded-md border text-gray-700 hover:bg-gray-50"
                    x-data x-on:click="$dispatch('close-modal', '{{ $name }}')">
                {{ $cancel }}
            </button>

            @if($action)
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @if(strtoupper($method) !== 'POST')
                        @method($method)
                    @endif
                    <button type="submit" class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">
                        {{ $confirm }}
                    </button>
                </form>
            @else
                <button type="button" class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700"
                        x-data x-on:click="$dispatch('confirm-modal:confirmed', { name: '{{ $name }}' })">
                    {{ $confirm }}
                </button>
            @endif
        </div>
    </div>
</x-shared::modal>
