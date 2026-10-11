/**
 * Turn canonical-text offsets back into one DOM Range.
 *
 * `canonical` is what `buildCanonicalText` returns. The range starts in the
 * first text node covering `[start, end)` and ends in the last one, so an
 * offset falling on a block newline or an emoji token (which have no text
 * node) snaps to the nearest text node edge inside the span. The DOM is not
 * touched.
 *
 * @param {{ text: string, nodeMap: Array<{start: number, end: number, domNode: Text}> }} canonical
 * @param {number} start
 * @param {number} end   exclusive
 * @returns {Range | null} null when the span is empty or covers no text node
 */
export function rangeFromCanonical(canonical, start, end) {
    if (!(start < end)) return null;

    const covered = canonical.nodeMap.filter(entry => entry.end > start && entry.start < end);
    if (covered.length === 0) return null;

    const first = covered[0];
    const last = covered[covered.length - 1];

    const range = first.domNode.ownerDocument.createRange();
    range.setStart(first.domNode, Math.max(start, first.start) - first.start);
    range.setEnd(last.domNode, Math.min(end, last.end) - last.start);
    return range;
}
