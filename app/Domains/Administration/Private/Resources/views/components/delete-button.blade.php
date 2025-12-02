@props([
    'action',
    'confirm' => null,
    'title' => null,
])

@php
    $confirmMessage = $confirm ?? __('administration::shared.confirm_delete');
    $escapedConfirm = str_replace("'", "\\'", $confirmMessage);
@endphp

<form method="POST" 
      action="{{ $action }}" 
      onsubmit="return confirm('{{ $escapedConfirm }}')"
      class="inline">
    @csrf
    @method('DELETE')
    <button type="submit"
            class="text-error hover:text-error/80"
            @if($title) title="{{ $title }}" @endif>
        <span class="material-symbols-outlined">delete</span>
    </button>
</form>
