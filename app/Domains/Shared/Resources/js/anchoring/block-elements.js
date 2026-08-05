/**
 * What actually is a block in a chapter's prose, for the purpose of
 * constraining a quote selection.
 *
 * A `div.ce-block` (the multi-block editor's wrapper) is the only boundary
 * that matters: it is one Quill instance, one authored unit. Paragraphs,
 * list items, headings, etc. inside it are just prose — a quote may freely
 * span several of them, only not cross into another editor block.
 */
export function isBlockElement(node) {
    if (!node || node.nodeType !== 1 /* ELEMENT_NODE */) return false;

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
