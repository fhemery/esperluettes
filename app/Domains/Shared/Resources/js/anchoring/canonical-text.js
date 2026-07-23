const BLOCK_TAGS = new Set(['P', 'BLOCKQUOTE', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'LI', 'PRE']);

/**
 * Build a plain-text representation of a DOM element and a nodeMap for offset conversion.
 *
 * - HTML tags are stripped.
 * - Custom emoji blots (<span class="ql-custom-emoji-{name}">) become :name:.
 * - Block-level element boundaries contribute a single space.
 *
 * @param {Element} rootEl
 * @returns {{ text: string, nodeMap: Array<{start: number, end: number, domNode: Text}> }}
 */
export function buildCanonicalText(rootEl) {
    let text = '';
    const nodeMap = [];

    function walk(node) {
        if (node.nodeType === 3 /* TEXT_NODE */) {
            const content = node.textContent;
            if (content.length > 0) {
                const start = text.length;
                text += content;
                nodeMap.push({ start, end: text.length, domNode: node });
            }
            return;
        }

        if (node.nodeType !== 1 /* ELEMENT_NODE */) return;

        const emojiClass = Array.from(node.classList).find(c => c.startsWith('ql-custom-emoji-'));
        if (emojiClass) {
            text += `:${emojiClass.slice('ql-custom-emoji-'.length)}:`;
            return;
        }

        for (const child of node.childNodes) {
            walk(child);
        }

        if (BLOCK_TAGS.has(node.tagName) && text.length > 0 && text[text.length - 1] !== ' ') {
            text += ' ';
        }
    }

    walk(rootEl);
    text = text.trimEnd();

    return { text, nodeMap };
}
