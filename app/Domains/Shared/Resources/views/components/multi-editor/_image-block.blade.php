{{--
    One image block of the multi-editor. Composes the Media image-field.

    Vars: $name (base), $uid, $scope, $path, $alt, $caption
--}}
<div class="ce-block ce-block--image border border-border rounded-lg p-3 mb-3 relative" data-block data-type="image" data-uid="{{ $uid }}">
    <div class="flex items-center justify-end gap-1 mb-2 text-fg/60">
        <button type="button" x-on:click="moveUp($el)" class="p-1 hover:text-fg" :title="labels.up"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></button>
        <button type="button" x-on:click="moveDown($el)" class="p-1 hover:text-fg" :title="labels.down"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></button>
        <button type="button" x-on:click="removeBlock($el)" class="p-1 hover:text-error" :title="labels.delete"><span class="material-symbols-outlined text-[18px]">delete</span></button>
    </div>

    <input type="hidden" name="{{ $name }}[{{ $uid }}][type]" value="image">

    <x-media::image-field
        :name="$name.'['.$uid.']'"
        :scope="$scope"
        :path="$path ?? null"
        :alt="$alt ?? ''"
        :caption="$caption ?? ''"
        :show-caption="true"
        :alt-required="true"
        :show-usage="false" />

    @include('shared::components.multi-editor._insert-affordance')
</div>
