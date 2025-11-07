<x-shared::app-layout>
    <x-shared::title icon="bookmark">{{ __('readlist::page.title') }}</x-shared::title>

    <div class="flex flex-col gap-4">
    @foreach($vm->stories as $story)
        <x-read-list::read-list-card :item="$story" />
    @endforeach
    </div>
</x-shared::app-layout>
