import { describe, it, expect } from 'vitest';
import { buildCanonicalText } from './canonical-text.js';

function makeEl(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div;
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

describe('buildCanonicalText', () => {
    it('extracts plain text from simple paragraph', () => {
        const { text } = buildCanonicalText(makeEl('<p>Hello world</p>'));
        expect(text).toBe('Hello world');
    });

    it('strips inline HTML tags', () => {
        const { text } = buildCanonicalText(makeEl('<p>Hello <strong>bold</strong> text</p>'));
        expect(text).toBe('Hello bold text');
    });

    it('adds one space at block boundaries between paragraphs', () => {
        const { text } = buildCanonicalText(makeEl('<p>First</p><p>Second</p>'));
        expect(text).toBe('First Second');
    });

    it('does not add double spaces when adjacent block elements are processed', () => {
        const { text } = buildCanonicalText(makeEl('<p>A</p><p>B</p><p>C</p>'));
        expect(text).toBe('A B C');
    });

    it('replaces custom emoji blot with :name: token', () => {
        const { text } = buildCanonicalText(makeEl('<p>Love <span class="ql-custom-emoji-heart"></span> you</p>'));
        expect(text).toBe('Love :heart: you');
    });

    it('does not recurse into emoji span children', () => {
        const { text } = buildCanonicalText(makeEl('<p>Hi <span class="ql-custom-emoji-wave"><img src="x.png"></span></p>'));
        expect(text).toBe('Hi :wave:');
    });

    it('handles blockquote as block element', () => {
        const { text } = buildCanonicalText(makeEl('<blockquote>Quote</blockquote><p>After</p>'));
        expect(text).toBe('Quote After');
    });

    it('returns empty string for empty element', () => {
        const { text } = buildCanonicalText(makeEl(''));
        expect(text).toBe('');
    });

    it('nodeMap entry start+end round-trips to the text node content', () => {
        const el = makeEl('<p>Hello world</p>');
        const { text, nodeMap } = buildCanonicalText(el);
        expect(nodeMap.length).toBeGreaterThan(0);
        for (const entry of nodeMap) {
            expect(text.slice(entry.start, entry.end)).toBe(entry.domNode.textContent);
        }
    });

    it('nodeMap covers all text nodes in document order', () => {
        const el = makeEl('<p>First</p><p>Second</p>');
        const { text, nodeMap } = buildCanonicalText(el);
        const textNodes = getTextNodes(el);
        expect(nodeMap.length).toBe(textNodes.length);
        for (const entry of nodeMap) {
            expect(text.slice(entry.start, entry.end)).toBe(entry.domNode.textContent);
        }
    });

    it('nodeMap entries are ordered with non-overlapping, contiguous offsets for inline content', () => {
        const el = makeEl('<p>one <em>two</em> three</p>');
        const { nodeMap } = buildCanonicalText(el);
        for (let i = 1; i < nodeMap.length; i++) {
            expect(nodeMap[i].start).toBeGreaterThanOrEqual(nodeMap[i - 1].end);
        }
    });
});
