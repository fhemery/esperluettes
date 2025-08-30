@props([
    'id' => null,
    'name',
    'checked' => false,
    'value' => '1',
    'label' => null,
    'disabled' => false,
])

<label class="inline-flex items-center cursor-pointer select-none">
    <input
        @if($id) id="{{ $id }}" @endif
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        class="sr-only peer"
        {{ $checked ? 'checked' : '' }}
        {{ $disabled ? 'disabled' : '' }}
    >
    <span class="relative inline-block w-11 h-6 rounded-full bg-gray-300 transition-colors align-middle
                 peer-checked:bg-indigo-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500
                 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform
                 peer-checked:after:translate-x-5"></span>
    @if($label)
        <span class="ml-3 text-sm text-gray-700">{{ $label }}</span>
    @endif
</label>
