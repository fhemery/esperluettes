{{--
    One choice of a chapter-choice block (see chapter-choice-block.blade.php).

    Vars: $field (`{name}[{uid}][choices][{i}]`), $choice (stored or submitted
          values, cast here), $chapters (ChapterChoiceTargets map).

    A stored target absent from $chapters was deleted: it stays selected as
    « Chapitre supprimé » with a warning, so an unrelated save keeps it.
--}}
@php
    $rawId = $choice['chapter_id'] ?? null;
    $chapterId = ($rawId === null || $rawId === '') ? null : (int) $rawId;
    $label = (string) ($choice['label'] ?? '');
    $enabled = array_key_exists('enabled', $choice) ? (bool) $choice['enabled'] : true;
    $deleted = $chapterId !== null && !isset($chapters[$chapterId]);
@endphp
<div data-choice class="flex flex-wrap items-end gap-2 p-2 rounded-md border border-border/60">
    <label class="flex flex-col gap-1 text-sm w-full sm:w-auto sm:flex-1 min-w-0">
        <span class="text-fg/70">{{ __('story::chapters.choice.target') }}</span>
        <select name="{{ $field }}[chapter_id]"
            class="w-full rounded-md border-accent focus:border-accent/80 focus:ring-accent surface-read text-on-surface text-sm">
            <option value=""></option>
            @foreach ($chapters as $target)
                <option value="{{ $target['id'] }}" @selected($target['id'] === $chapterId)>{{ $target['published'] ? $target['title'] : $target['title'] . ' (' . __('story::chapters.choice.unpublished') . ')' }}</option>
            @endforeach
            @if ($deleted)
                <option value="{{ $chapterId }}" selected>{{ __('story::chapters.choice.deleted') }}</option>
            @endif
        </select>
    </label>

    <label class="flex flex-col gap-1 text-sm w-full sm:w-auto sm:flex-1 min-w-0">
        <span class="text-fg/70">{{ __('story::chapters.choice.label') }}</span>
        <input type="text" name="{{ $field }}[label]" value="{{ $label }}" maxlength="120"
            placeholder="{{ __('story::chapters.choice.label_placeholder') }}"
            class="w-full rounded-md border-accent focus:border-accent/80 focus:ring-accent surface-read text-on-surface text-sm">
    </label>

    <label class="flex items-center gap-2 text-sm py-2">
        {{-- An unchecked box submits nothing: the hidden 0 is then the value. --}}
        <input type="hidden" name="{{ $field }}[enabled]" value="0">
        <input type="checkbox" name="{{ $field }}[enabled]" value="1" @checked($enabled)
            class="rounded border-accent text-accent focus:ring-accent">
        {{ __('story::chapters.choice.enabled') }}
    </label>

    <div class="flex items-center gap-1 text-fg/60 py-1">
        <button type="button" x-on:click="up($el)" class="p-1 hover:text-fg"
            aria-label="{{ __('story::chapters.choice.move_up') }}" title="{{ __('story::chapters.choice.move_up') }}">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_upward</span>
        </button>
        <button type="button" x-on:click="down($el)" class="p-1 hover:text-fg"
            aria-label="{{ __('story::chapters.choice.move_down') }}" title="{{ __('story::chapters.choice.move_down') }}">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_downward</span>
        </button>
        <button type="button" x-on:click="remove($el)" class="p-1 hover:text-error"
            aria-label="{{ __('story::chapters.choice.remove') }}" title="{{ __('story::chapters.choice.remove') }}">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">close</span>
        </button>
    </div>

    @if ($deleted)
        <p class="w-full text-sm text-error flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">warning</span>{{ __('story::chapters.choice.deleted_warning') }}
        </p>
    @endif
</div>
