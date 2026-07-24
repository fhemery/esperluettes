const MAX_HIGHLIGHT_LENGTH = 500;
const CONTEXT_WORDS = 5;

/**
 * Extract an anchor from a DOM Range against the canonical text.
 *
 * @param {Range|{startContainer:Text,startOffset:number,endContainer:Text,endOffset:number}} range
 * @param {Element} rootEl  (unused — kept for API symmetry)
 * @param {{ text: string, nodeMap: Array<{start:number,end:number,domNode:Text}> }} canonicalText
 * @returns {{ highlighted: string, prefix: string, suffix: string } | null}
 *   null when the selection exceeds MAX_HIGHLIGHT_LENGTH or cannot be located.
 */
export function extractAnchor(range, rootEl, canonicalText) {
    const { text, nodeMap } = canonicalText;

    const startCanonical = domOffsetToCanonical(range.startContainer, range.startOffset, nodeMap);
    const endCanonical = domOffsetToCanonical(range.endContainer, range.endOffset, nodeMap);

    if (startCanonical === null || endCanonical === null || startCanonical >= endCanonical) {
        return null;
    }

    const highlighted = text.slice(startCanonical, endCanonical);

    if (highlighted.replace(/\s/g, '').length === 0) {
        return null;
    }

    if (highlighted.length > MAX_HIGHLIGHT_LENGTH) {
        return null;
    }

    const prefix = lastNWords(text.slice(0, startCanonical), CONTEXT_WORDS);
    const suffix = firstNWords(text.slice(endCanonical), CONTEXT_WORDS);

    return { highlighted, prefix, suffix };
}

function domOffsetToCanonical(container, charOffset, nodeMap) {
    for (const entry of nodeMap) {
        if (entry.domNode === container) {
            return entry.start + charOffset;
        }
    }
    return null;
}

function lastNWords(str, n) {
    const words = str.trim().split(/\s+/).filter(Boolean);
    return words.slice(-n).join(' ');
}

function firstNWords(str, n) {
    const words = str.trim().split(/\s+/).filter(Boolean);
    return words.slice(0, n).join(' ');
}
