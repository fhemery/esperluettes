{{--
    <x-editor::multi.block> — the chrome every multi-editor block must carry,
    for plugin block partials (other domains) to wrap their own fields in:
    root `[data-block][data-type][data-uid]`, move/delete controls, the hidden
    `{name}[{uid}][type]` input, the default slot, then the "+" insert menu.

    Props: type (block key), name (base field), uid.

    The insert menu lists the types enabled on the enclosing <x-editor::multi>,
    read with @aware — plugin partials do not pass them.
--}}
@props(['type', 'name', 'uid'])
@aware(['blockTypes' => ['text', 'image']])

@php
    $enabledTypes = array_values(array_filter(
        app(\App\Domains\Editor\Public\Blocks\EditorBlockRegistry::class)->all(),
        fn ($t) => in_array($t->key(), $blockTypes, true),
    ));
@endphp

<div class="ce-block ce-block--{{ $type }} border border-border rounded-lg p-3 mb-3 relative" data-block data-type="{{ $type }}" data-uid="{{ $uid }}">
    <div class="flex items-center justify-end gap-1 mb-2 text-fg/60">
        <button type="button" x-on:click="moveUp($el)" class="p-1 hover:text-fg" :title="labels.up"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></button>
        <button type="button" x-on:click="moveDown($el)" class="p-1 hover:text-fg" :title="labels.down"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></button>
        <button type="button" x-on:click="removeBlock($el)" class="p-1 hover:text-error" :title="labels.delete"><span class="material-symbols-outlined text-[18px]">delete</span></button>
    </div>

    <input type="hidden" name="{{ $name }}[{{ $uid }}][type]" value="{{ $type }}">

    {{ $slot }}

    @include('editor::components.multi._insert-affordance', ['enabledTypes' => $enabledTypes])
</div>
