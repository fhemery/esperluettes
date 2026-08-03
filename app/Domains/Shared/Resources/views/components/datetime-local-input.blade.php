@props(['disabled' => false])

{{--
    Renders as a plain datetime-local input to the server (value stays UTC in
    the DOM at all times, so `old()` re-population and server-side validation
    are untouched). Alpine swaps the *displayed* value to the browser's local
    time on load, and swaps it back to UTC right before the form submits.
--}}
<input type="datetime-local" @disabled($disabled)
    {{ $attributes->merge(['class' => 'border-accent focus:border-accent/80 focus:ring-accent surface-read text-on-surface']) }}
    x-data
    x-init="
        if ($el.value) {
            $el.value = window.DateUtils.utcToLocalInput($el.value);
            $el.dispatchEvent(new Event('input', { bubbles: true }));
        }
        $el.closest('form')?.addEventListener('submit', () => {
            if ($el.value) $el.value = window.DateUtils.localToUtcInput($el.value);
        });
    "
>
