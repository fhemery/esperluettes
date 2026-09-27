{{--
    Editor partial of the `chapter-choice` block (registered by ChapterChoiceBlockType).

    Vars: $name (base field), $uid, $block (null for a new block, else the stored
          or old-input block), $context['chapters'] (ChapterChoiceTargets map:
          every chapter of the story, reading order, all statuses).

    Choice rows are server-rendered; the local Alpine scope only adds, removes and
    moves rows in the DOM, then re-indexes their field names so the submitted
    order is the order on screen. Method names differ from the multiEditor's
    moveUp/moveDown/removeBlock, which the block chrome and e2e selectors use.
--}}
@php
    $chapters = is_array($context['chapters'] ?? null) ? $context['chapters'] : [];
    $choices = $block === null
        ? [['chapter_id' => null, 'label' => '', 'enabled' => true]]
        : array_values(array_filter((array) ($block['choices'] ?? []), 'is_array'));
@endphp
<x-editor::multi.block type="chapter-choice" :name="$name" :uid="$uid">
    <div data-choice-block
        x-data="{
            list(el) { return el.closest('[data-choice-block]').querySelector('[data-choice-list]'); },
            reindex(list) {
                Array.from(list.children).forEach((row, i) => {
                    row.querySelectorAll('[name]').forEach((f) => {
                        f.name = f.name.replace(/\[choices\]\[[^\]]*\]/, '[choices][' + i + ']');
                    });
                });
            },
            add(el) {
                const list = this.list(el);
                const tpl = el.closest('[data-choice-block]').querySelector(':scope > template[data-choice-template]');
                list.appendChild(tpl.content.firstElementChild.cloneNode(true));
                this.reindex(list);
            },
            remove(el) {
                const list = this.list(el);
                el.closest('[data-choice]').remove();
                this.reindex(list);
            },
            {{-- The sibling moves, not the row, so the pressed button keeps focus. --}}
            up(el) {
                const row = el.closest('[data-choice]');
                const prev = row.previousElementSibling;
                if (prev) row.after(prev);
                this.reindex(this.list(el));
            },
            down(el) {
                const row = el.closest('[data-choice]');
                const next = row.nextElementSibling;
                if (next) row.before(next);
                this.reindex(this.list(el));
            },
        }">
        <p class="flex items-center gap-1 text-sm font-semibold text-fg mb-2">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">alt_route</span>{{ __('story::chapters.choice.block_label') }}
        </p>

        <div data-choice-list class="flex flex-col gap-2">
            @foreach ($choices as $i => $choice)
                @include('story::editor.chapter-choice-row', [
                    'field' => $name . '[' . $uid . '][choices][' . $i . ']',
                    'choice' => $choice,
                    'chapters' => $chapters,
                ])
            @endforeach
        </div>

        <template data-choice-template>
            @include('story::editor.chapter-choice-row', [
                'field' => $name . '[' . $uid . '][choices][__CHOICE__]',
                'choice' => ['chapter_id' => null, 'label' => '', 'enabled' => true],
                'chapters' => $chapters,
            ])
        </template>

        <div class="mt-2">
            <button type="button" x-on:click="add($el)"
                class="px-3 py-1.5 text-sm rounded-md border border-border text-primary hover:bg-primary/5 inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add</span>{{ __('story::chapters.choice.add') }}
            </button>
        </div>
    </div>
</x-editor::multi.block>
