{{-- Test-only plugin block partial: the chrome comes from <x-editor::multi.block>. --}}
<x-editor::multi.block type="fake" :name="$name" :uid="$uid">
    <span class="fake-marker">{{ $context['marker'] ?? '' }}</span>
    <input type="hidden" name="{{ $name }}[{{ $uid }}][value]" value="fake-value:{{ $block['value'] ?? '' }}">
</x-editor::multi.block>
