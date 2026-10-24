@props(['name' => null, 'id' => null, 'value' => '', 'disabled' => false, 'mirrorOf' => null, 'submit' => 'utc', 'min' => null])

{{--
    A moment is a date input and a time input over one hidden input carrying
    the original `name`. The hidden value is the server's UTC string on render;
    Alpine converts it to the browser's local time while editing (so the
    visible pair, and anything listening to the hidden input's `input` event,
    sees local time), and back to UTC right before the form submits.

    `mirror-of="<other component's id>"` renders a read-only copy that follows
    the other component's hidden input (id `{id}_value`): no hidden input of
    its own, nothing submitted.

    `submit="local"` skips the back-to-UTC step: the local value is submitted
    as typed (the form carries its own timezone field). `min` bounds the date
    input only.
--}}
@php
    $inputClass = 'min-w-0 flex-1 border-accent focus:border-accent/80 focus:ring-accent surface-read text-on-surface '
        . $attributes->get('class');
    $isMirror = $mirrorOf !== null;
    $hiddenId = $id ? $id . '_value' : null;
@endphp

<div role="group" class="flex flex-wrap gap-2"
    x-data="{
        submit: '{{ $submit === 'local' ? 'local' : 'utc' }}',
        date: '',
        time: '',
        hidden() { return this.$refs.hidden; },
        write(value) {
            this.hidden().value = value;
            this.hidden().dispatchEvent(new Event('input', { bubbles: true }));
        },
        split(value) {
            const parts = window.DateUtils.splitLocalInput(value);
            this.date = parts.date;
            this.time = parts.time;
        },
        edit() {
            if (!this.date) this.time = '';
            else if (!this.time) this.time = '00:00';
            this.write(window.DateUtils.joinLocalInput(this.date, this.time));
        },
        init() {
            @if ($isMirror)
            const source = document.getElementById(@js($mirrorOf . '_value'));
            this.split(source?.value ?? '');
            source?.addEventListener('input', () => this.split(source.value));
            @else
            const hidden = this.hidden();
            if (hidden.value) {
                hidden.value = window.DateUtils.utcToLocalInput(hidden.value);
                this.split(hidden.value);
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
            }
            this.$root.closest('form')?.addEventListener('submit', () => {
                if (this.submit === 'utc' && hidden.value) hidden.value = window.DateUtils.localToUtcInput(hidden.value);
            });
            @endif
        },
    }"
>
    <input type="date" @if ($id) id="{{ $id }}" @endif @if ($min) min="{{ $min }}" @endif
        x-model="date" @if (! $isMirror) x-on:input="edit()" @endif
        @disabled($disabled || $isMirror) @if ($isMirror) aria-disabled="true" @endif
        class="{{ $inputClass }}">
    <input type="time"
        aria-label="{{ __('shared::components.datetime_input.time') }}"
        x-model="time" @if (! $isMirror) x-on:input="edit()" @endif
        @disabled($disabled || $isMirror) @if ($isMirror) aria-disabled="true" @endif
        class="{{ $inputClass }}">
    @unless ($isMirror)
        <input type="hidden" x-ref="hidden" name="{{ $name }}"
            @if ($hiddenId) id="{{ $hiddenId }}" @endif
            value="{{ $value }}" @disabled($disabled)>
    @endunless
</div>
