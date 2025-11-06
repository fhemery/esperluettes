<x-shared::app-layout>
    <x-shared::title icon="bookmark">{{ __('readlist::page.title') }}</x-shared::title>

    @foreach($vm->stories as $story)
        <x-read-list::read-list-card :item="$story" />
    @endforeach
</x-shared::app-layout>
