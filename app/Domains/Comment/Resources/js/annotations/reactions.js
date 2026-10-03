import { readSelection } from './capture-form.js';

/**
 * One-click emoji reactions (`<x-comment::reaction-buttons>`): the emoji
 * becomes an annotation body `<p>EMOJI</p>` anchored to the current selection,
 * with no form. It goes to the draft slot or to the pending adds per the
 * selection's `[data-annotable]` `data-annotation-mode`. Same refusal rules as
 * « Annoter »: a multi-block or over-cap selection is ignored.
 *
 * The component lives in the cloned selection toolbar (outside the annotable),
 * so the entity and the mode come from the region holding the selection;
 * the user id and the highlight cap from the component root.
 */
export function annotationReactions() {
    return {
        react(emoji) {
            const read = readSelection(Number(this.$root.dataset.highlightMaxLength));
            if (!read || !read.anchor || read.tooLong || read.multiBlock) return;

            const region = read.articleEl.closest('[data-annotable]');
            if (!region) return;

            const userId = parseInt(this.$root.dataset.userId ?? '', 10) || null;
            const { entityType, entityId, annotationMode } = region.dataset;
            const item = {
                body: `<p>${emoji}</p>`,
                highlighted: read.anchor.highlighted,
                prefix: read.anchor.prefix ?? '',
                suffix: read.anchor.suffix ?? '',
            };
            if (annotationMode === 'pending') {
                window.commentDrafts?.addPendingAnnotation(userId, entityType, entityId, item);
            } else {
                window.commentDrafts?.addAnnotation(userId, entityType, entityId, item);
            }

            // Clearing the selection hides the toolbar.
            read.selection.removeAllRanges();
        },
    };
}
