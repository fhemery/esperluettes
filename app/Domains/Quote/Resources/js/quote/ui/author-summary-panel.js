import { groupPassages } from './author-summary.js';
import { resolveRows } from './author-anchoring.js';

/**
 * The chapter summary: one row per quoted passage, opened from the citations
 * badge. It exists because the badge counts stale quotes the heat cannot show —
 * this is where that gap is made legible.
 *
 * It works with the heat off, so it re-anchors the rows itself rather than
 * reading anything the heat may or may not have rendered.
 */
export function quoteAuthorSummary({ chapterId = 0, countLabelOne = '', countLabelOther = '' } = {}) {
    return {
        chapterId,
        countLabelOne,
        countLabelOther,
        groups: [],
        _built: false,

        /** Called on every open; does the work once. */
        async load() {
            if (this._built) return;
            this._built = true;

            const store = Alpine.store('quoteAggregate');
            await store.ensureLoaded(this.chapterId);
            this.groups = groupPassages(
                resolveRows(store.rows, document.querySelector('[data-quote-article]'))
            );
        },

        /**
         * Close the popup, then hand over to the heat: turn it on if it was off,
         * scroll to the passage and open its popover (decision #11). Stale rows
         * have no location to scroll to and are inert — the guard here backs up
         * the template, which renders no button for them at all.
         */
        select(group) {
            if (!group || group.stale) return;

            this._closePopup();
            Alpine.store('quoteAggregate').focus(group.key);
        },

        countLabel(count) {
            const template = count > 1 ? this.countLabelOther : this.countLabelOne;
            return template.replace('{count}', String(count));
        },

        /** `hoverOpen` / `pinned` / `updateOpen` belong to the enclosing popover. */
        _closePopup() {
            this.hoverOpen = false;
            this.pinned = false;
            this.updateOpen?.();
        },
    };
}
