import { describe, it, expect } from 'vitest';
import { extractAnchor } from './extract-anchor.js';
import { buildCanonicalText } from './canonical-text.js';

function makeEl(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div;
}

function makeRange(startNode, startOffset, endNode, endOffset) {
    return { startContainer: startNode, startOffset, endContainer: endNode, endOffset };
}

function getTextNodes(el) {
    const nodes = [];
    function collect(n) {
        if (n.nodeType === 3) nodes.push(n);
        else for (const c of n.childNodes) collect(c);
    }
    collect(el);
    return nodes;
}

describe('extractAnchor', () => {
    it('extracts highlighted text and context for a mid-paragraph selection', () => {
        // "The quick brown fox jumps over the lazy dog"
        const el = makeEl('<p>The quick brown fox jumps over the lazy dog</p>');
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);

        const start = textNode.textContent.indexOf('brown fox');
        const end = start + 'brown fox'.length;
        const anchor = extractAnchor(makeRange(textNode, start, textNode, end), el, canonical);

        expect(anchor).not.toBeNull();
        expect(anchor.highlighted).toBe('brown fox');
        expect(anchor.prefix).toBe('The quick');
        expect(anchor.suffix).toBe('jumps over the lazy dog');
    });

    it('returns empty prefix when selection is at the chapter start', () => {
        const el = makeEl('<p>Hello world is great today</p>');
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);

        const anchor = extractAnchor(makeRange(textNode, 0, textNode, 5), el, canonical);

        expect(anchor).not.toBeNull();
        expect(anchor.highlighted).toBe('Hello');
        expect(anchor.prefix).toBe('');
    });

    it('returns empty suffix when selection is at the chapter end', () => {
        const el = makeEl('<p>Hello world is great today</p>');
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);
        const text = textNode.textContent;

        const anchor = extractAnchor(makeRange(textNode, text.length - 5, textNode, text.length), el, canonical);

        expect(anchor).not.toBeNull();
        expect(anchor.highlighted).toBe('today');
        expect(anchor.suffix).toBe('');
    });

    it('limits prefix and suffix to 5 words', () => {
        const el = makeEl('<p>one two three four five six seven eight SELECTED one two three four five six seven eight</p>');
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);
        const text = textNode.textContent;

        const start = text.indexOf('SELECTED');
        const end = start + 'SELECTED'.length;
        const anchor = extractAnchor(makeRange(textNode, start, textNode, end), el, canonical);

        expect(anchor.prefix.split(' ').length).toBeLessThanOrEqual(5);
        expect(anchor.suffix.split(' ').length).toBeLessThanOrEqual(5);
        expect(anchor.prefix).toBe('four five six seven eight');
        expect(anchor.suffix).toBe('one two three four five');
    });

    it('handles selection spanning a paragraph boundary', () => {
        const el = makeEl('<p>First paragraph</p><p>second paragraph</p>');
        const canonical = buildCanonicalText(el);
        const textNodes = getTextNodes(el);
        const [firstNode, secondNode] = textNodes;

        // Select from "paragraph" in first to "second" in second
        const start = firstNode.textContent.indexOf('paragraph');
        const anchor = extractAnchor(makeRange(firstNode, start, secondNode, 6), el, canonical);

        expect(anchor).not.toBeNull();
        expect(anchor.highlighted).toContain('paragraph');
        expect(anchor.highlighted).toContain('second');
    });

    it('returns null when selection exceeds 500 plain-text characters', () => {
        const longText = 'a'.repeat(501);
        const el = makeEl(`<p>${longText}</p>`);
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);

        const anchor = extractAnchor(makeRange(textNode, 0, textNode, 501), el, canonical);

        expect(anchor).toBeNull();
    });

    it('returns anchor for a selection of exactly 500 characters', () => {
        const text500 = 'a'.repeat(500);
        const el = makeEl(`<p>${text500}</p>`);
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);

        const anchor = extractAnchor(makeRange(textNode, 0, textNode, 500), el, canonical);

        expect(anchor).not.toBeNull();
        expect(anchor.highlighted.length).toBe(500);
    });

    it('returns null for a whitespace-only selection', () => {
        const el = makeEl('<p>Hello   world</p>');
        const canonical = buildCanonicalText(el);
        const [textNode] = getTextNodes(el);

        // Select the three spaces between Hello and world (indices 5, 6, 7)
        const anchor = extractAnchor(makeRange(textNode, 5, textNode, 8), el, canonical);

        expect(anchor).toBeNull();
    });

    it('returns null when range container is not in the nodeMap', () => {
        const el = makeEl('<p>Hello world</p>');
        const canonical = buildCanonicalText(el);
        const foreignNode = document.createTextNode('foreign');

        const anchor = extractAnchor(makeRange(foreignNode, 0, foreignNode, 5), el, canonical);

        expect(anchor).toBeNull();
    });
});
