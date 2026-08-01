import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { findAnchor } from '../../../../../Shared/Resources/js/anchoring/reanchor.js';

/**
 * Re-anchor aggregate rows against a chapter's canonical text.
 *
 * `groupPassages()` is pure and cannot know a row is stale on its own: the
 * caller annotates each row with a resolved `range` (`{start, end}`) or `null`
 * beforehand. Both consumers of that contract — the heat and the chapter
 * summary — go through here, so they can never disagree on what is stale.
 *
 * @param {Object} canonical output of `buildCanonicalText()`
 * @param {Array<Object>} rows
 * @returns {Array<Object>} the same rows, each carrying `range`
 */
export function annotateRanges(canonical, rows) {
    return rows.map(row => {
        const result = findAnchor(canonical, {
            prefix: row.prefix ?? '',
            highlighted: row.highlighted_text ?? '',
            suffix: row.suffix ?? '',
        });

        return {
            ...row,
            range: result.status === 'missing' ? null : { start: result.start, end: result.end },
        };
    });
}

/**
 * Same, for a caller that has no use for the canonical text itself. Without an
 * article element every row is stale.
 *
 * @param {Array<Object>} rows
 * @param {Element|null} articleEl
 * @returns {Array<Object>}
 */
export function resolveRows(rows, articleEl) {
    if (!articleEl) return rows.map(row => ({ ...row, range: null }));

    return annotateRanges(buildCanonicalText(articleEl), rows);
}
