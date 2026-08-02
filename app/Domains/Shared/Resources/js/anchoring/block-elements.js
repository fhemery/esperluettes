/**
 * What actually is a block in a chapter's prose.
 *
 * Deliberately narrower than `canonical-text.js`'s BLOCK_TAGS: that set contains
 * DIV, which would make any decorative wrapper look like a block boundary. Here
 * only a `div.ce-block` (the block-editor wrapper) counts as one.
 */
const BLOCK_TAGS = new Set(['P', 'BLOCKQUOTE', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'LI', 'PRE']);

/**
 * @param {Node|null} node
 * @returns {boolean}
 */
export function isBlockElement(node) {
    if (!node || node.nodeType !== 1 /* ELEMENT_NODE */) return false;
    if (BLOCK_TAGS.has(node.tagName)) return true;

    return node.tagName === 'DIV' && node.classList.contains('ce-block');
}

/**
 * Nearest block ancestor of a node, itself included.
 *
 * @param {Node|null} node
 * @returns {Element|null}
 */
export function closestBlock(node) {
    let current = node && node.nodeType === 1 ? node : node?.parentElement ?? null;

    while (current) {
        if (isBlockElement(current)) return current;
        current = current.parentElement;
    }

    return null;
}
