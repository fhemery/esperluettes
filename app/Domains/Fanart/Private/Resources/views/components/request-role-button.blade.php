@if ($pendingId !== null)
    <form action="{{ route('fanart.role-requests.destroy', ['slug' => $slug, 'request' => $pendingId]) }}" method="POST">
        @csrf
        @method('DELETE')
        <x-shared::button color="neutral" type="submit">{{ __('fanart::ui.role_request.pending') }}</x-shared::button>
    </form>
@elseif ($canRequest)
    <form action="{{ route('fanart.role-requests.store', ['slug' => $slug]) }}" method="POST">
        @csrf
        <x-shared::button color="accent" type="submit">{{ __('fanart::ui.role_request.' . ($label ?? 'request')) }}</x-shared::button>
    </form>
@endif
