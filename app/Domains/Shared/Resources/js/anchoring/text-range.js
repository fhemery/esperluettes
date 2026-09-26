/**
 * The text a selection range really covers.
 *
 * A browser range may have a boundary that touches a block without covering
 * any of its text — a triple-click ends at `(nextBlock, 0)`, a drag may start
 * at the very end of the previous text node. Such a boundary is not part of
 * the selection: callers that decide what was selected (the toolbar's area
 * check, anchor extraction) go through here so they agree on it.
 *
 * Accepts a DOM Range or any `{startContainer, startOffset, endContainer, endOffset}`.
 */

function toDomRange(range) {
    const r = range.startContainer.ownerDocument.createRange();
    r.setStart(range.startContainer, range.startOffset);
    r.setEnd(range.endContainer, range.endOffset);
    return r;
}

/**
 * Non-blank text slices covered by the range, in document order.
 *
 * @returns {Array<{node: Text, start: number, end: number}>}
 */
export function coveredTextSlices(range) {
    const r = toDomRange(range);
    const root = r.commonAncestorContainer;
    const nodes = [];
    if (root.nodeType === 3 /* TEXT_NODE */) {
        nodes.push(root);
    } else {
        const walker = root.ownerDocument.createTreeWalker(root, 4 /* NodeFilter.SHOW_TEXT */);
        while (walker.nextNode()) nodes.push(walker.currentNode);
    }

    const slices = [];
    for (const node of nodes) {
        if (!r.intersectsNode(node)) continue;
        const start = node === r.startContainer ? r.startOffset : 0;
        const end = node === r.endContainer ? r.endOffset : node.length;
        if (node.data.slice(start, end).trim()) slices.push({ node, start, end });
    }
    return slices;
}

/**
 * The range narrowed to its first and last covered text, both boundaries on
 * text nodes; null when it covers no text at all.
 *
 * @returns {{startContainer: Text, startOffset: number, endContainer: Text, endOffset: number} | null}
 */
export function trimRangeToText(range) {
    const slices = coveredTextSlices(range);
    if (slices.length === 0) return null;

    const first = slices[0];
    const last = slices[slices.length - 1];
    return {
        startContainer: first.node,
        startOffset: first.start,
        endContainer: last.node,
        endOffset: last.end,
    };
}
