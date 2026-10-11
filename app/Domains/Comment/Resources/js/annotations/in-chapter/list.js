import { annotatorName } from './annotator.js';
import { rowKey } from './gutter.js';

/** Asks the overlay to show the annotations, scroll to the row's avatar and open its popover. */
export const FOCUS_EVENT = 'annotation-overlay:focus';

const EXCERPT_LENGTH = 90;

/**
 * The header badge's list of the chapter's annotations,
 * `Alpine.data('annotationList')`: every row of `Alpine.store('annotationOverlay')`
 * (the display toggle and the author's filters do not apply), one line per
 * annotation. Choosing one dispatches `FOCUS_EVENT`, which the overlay answers.
 *
 * The labels and the commenter's own profile come from the overlay root, which
 * already carries them.
 */
export function annotationList() {
    let unknownName = '';
    let defaultAvatar = '';
    let own = { name: '', avatar: '' };

    return {
        init() {
            const data = document.querySelector('[data-annotation-overlay]')?.dataset ?? {};
            unknownName = data.unknownName ?? '';
            defaultAvatar = data.defaultAvatar ?? '';
            own = { name: data.userName ?? '', avatar: data.userAvatar ?? '' };
        },

        get rows() {
            return this.$store.annotationOverlay.rows;
        },

        get count() {
            return this.rows.length;
        },

        /** [{ key, name, avatar, text, processed }] in reading order of the lists. */
        get items() {
            return this.rows.map((row) => {
                const local = row.source && row.source !== 'server';
                const text = (row.highlighted_text ?? '').trim();
                return {
                    key: rowKey(row),
                    name: local ? own.name : annotatorName(row.author_profile, unknownName),
                    avatar: (local ? own.avatar : row.author_profile?.avatar_url) || defaultAvatar,
                    text: text.length > EXCERPT_LENGTH ? `${text.slice(0, EXCERPT_LENGTH)}…` : text,
                    processed: row.is_processed === true,
                };
            });
        },

        select(item) {
            window.dispatchEvent(new CustomEvent(FOCUS_EVENT, { detail: { key: item.key } }));
        },
    };
}
