@if ($shouldRender)
<form method="POST" action="{{ $isInReadList ? route('readlist.remove', $storyId) : route('readlist.add', $storyId) }}">
    @csrf
    @if ($isInReadList)
        @method('DELETE')
    @endif
    
    <x-shared::button
        type="submit"
        color="tertiary"
        :outline="$isInReadList"
        :icon="$isInReadList ? 'check' : null"
        size="md">
        {{ $isInReadList ? __('readlist::button.in_readlist_button') : __('readlist::button.add_button') }}
    </x-shared::button>
</form>
@endif
