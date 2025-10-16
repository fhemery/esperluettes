@props([
    'action',
    'method' => 'POST',
    'label',
    'color' => 'neutral',
    'confirm' => null,
])

<form action="{{ $action }}" method="POST">
    @csrf
    @if (!in_array(strtoupper($method), ['GET','POST']))
        @method($method)
    @endif
    <x-shared::button type="submit" :color="$color">
        {{ $label }}
    </x-shared::button>
</form>
