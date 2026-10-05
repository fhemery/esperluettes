import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { findAnchor } from '../../../../../Shared/Resources/js/anchoring/reanchor.js';
import { rangeFromCanonical } from '../../../../../Shared/Resources/js/anchoring/canonical-range.js';
import { ANNOTATABLE_AREA_SELECTOR } from '../capture-form.js';

/** Names of the CSS Custom Highlights, styled in overlay.css. */
export const HIGHLIGHT_ANNOTATION = 'annotation';
export const HIGHLIGHT_PROCESSED = 'annotation-processed';

/**
 * Re-anchor `rows` in the article and tint their passages with the CSS Custom
 * Highlight API. The DOM is never touched, so Quote's marks keep working; but
 * Quote's re-wraps invalidate the ranges, hence one full rebuild per call.
 * `missing` passages are dropped. Without `CSS.highlights` nothing is tinted,
 * the ranges are still returned (the gutter positions from them).
 *
 * @returns {Array<{ row: object, range: Range }>}
 */
export function renderHighlights(articleEl, rows) {
    const placed = [];
    if (rows.length > 0) {
        const canonical = buildCanonicalText(articleEl, { within: ANNOTATABLE_AREA_SELECTOR });
        for (const row of rows) {
            const anchor = findAnchor(canonical, {
                prefix: row.prefix ?? '',
                highlighted: row.highlighted_text ?? '',
                suffix: row.suffix ?? '',
            });
            if (anchor.status === 'missing') continue;
            const range = rangeFromCanonical(canonical, anchor.start, anchor.end);
            if (range) placed.push({ row, range });
        }
    }

    const registry = globalThis.CSS?.highlights;
    if (!registry || typeof globalThis.Highlight !== 'function') return placed;

    const plain = placed.filter((p) => p.row.is_processed !== true).map((p) => p.range);
    const processed = placed.filter((p) => p.row.is_processed === true).map((p) => p.range);
    setHighlight(registry, HIGHLIGHT_ANNOTATION, plain);
    setHighlight(registry, HIGHLIGHT_PROCESSED, processed);
    return placed;
}

function setHighlight(registry, name, ranges) {
    if (ranges.length === 0) registry.delete(name);
    else registry.set(name, new globalThis.Highlight(...ranges));
}
