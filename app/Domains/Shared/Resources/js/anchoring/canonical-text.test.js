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

    it('adds one newline at block boundaries between paragraphs', () => {
        const { text } = buildCanonicalText(makeEl('<p>First</p><p>Second</p>'));
        expect(text).toBe('First\nSecond');
    });

    it('does not add double newlines when adjacent block elements are processed', () => {
        const { text } = buildCanonicalText(makeEl('<p>A</p><p>B</p><p>C</p>'));
        expect(text).toBe('A\nB\nC');
    });

    it('does not double the newline when the stored HTML already has one between sibling paragraphs', () => {
        // Real chapter content stores a literal "\n" between sibling <p> tags
        // (formatting, not authored content) — it must not stack with the
        // boundary's own newline into a blank line.
        const { text } = buildCanonicalText(makeEl('<p>First</p>\n<p>Second</p>'));
        expect(text).toBe('First\nSecond');
    });

    it('keeps a genuine inline space between two inline elements', () => {
        const { text } = buildCanonicalText(makeEl('<p><em>Hello</em> <strong>world</strong></p>'));
        expect(text).toBe('Hello world');
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
        expect(text).toBe('Quote\nAfter');
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

describe('buildCanonicalText — within filter', () => {
    const WITHIN = '.ce-block--text';
    const MIXED = '<div class="ce-block ce-block--text"><p>avant</p></div>'
        + '<figure class="ce-block ce-block--image"><img><figcaption>légende</figcaption></figure>'
        + '<div class="ce-block ce-block--text"><p>après</p></div>';

    it('excludes a figure and its caption between two matching blocks', () => {
        const { text } = buildCanonicalText(makeEl(MIXED), { within: WITHIN });
        expect(text).toBe('avant\naprès');
    });

    it('maps nodeMap offsets back to the matching text nodes', () => {
        const el = makeEl(MIXED);
        const { text, nodeMap } = buildCanonicalText(el, { within: WITHIN });
        expect(nodeMap.length).toBe(2);
        for (const entry of nodeMap) {
            expect(entry.domNode.textContent).toBe(text.slice(entry.start, entry.end));
            expect(entry.domNode.parentElement.closest('figcaption')).toBeNull();
        }
    });

    it('ignores whitespace text nodes between areas', () => {
        const el = makeEl(
            '<div class="ce-block ce-block--text"><p>avant</p></div>\n'
            + '<figure class="ce-block ce-block--image"><figcaption>légende</figcaption></figure>\n'
            + '<div class="ce-block ce-block--text"><p>après</p></div>',
        );
        const { text, nodeMap } = buildCanonicalText(el, { within: WITHIN });
        expect(text).toBe('avant\naprès');
        expect(nodeMap.map(e => e.domNode.textContent)).toEqual(['avant', 'après']);
    });

    it('skips emoji blots outside the area and keeps them inside', () => {
        const el = makeEl(
            '<div class="ce-block ce-block--text"><p>Love <span class="ql-custom-emoji-heart"></span> you</p></div>'
            + '<figure class="ce-block ce-block--image"><figcaption>Hi <span class="ql-custom-emoji-wave"></span></figcaption></figure>',
        );
        const { text } = buildCanonicalText(el, { within: WITHIN });
        expect(text).toBe('Love :heart: you');
    });

    it('returns empty text when nothing matches', () => {
        const { text, nodeMap } = buildCanonicalText(makeEl('<p>a</p><p>b</p>'), { within: WITHIN });
        expect(text).toBe('');
        expect(nodeMap).toEqual([]);
    });

    it('is unchanged when within is omitted', () => {
        // Current output, pinned: FIGURE/FIGCAPTION are not in BLOCK_TAGS, so
        // the caption runs into the next block without a newline.
        const { text } = buildCanonicalText(makeEl(MIXED));
        expect(text).toBe('avant\nlégendeaprès');
    });

    it('yields the same text for Simple content wrapped in one area', () => {
        const unwrapped = buildCanonicalText(makeEl('<p>a</p>\n<p>b</p>'));
        const wrapped = buildCanonicalText(
            makeEl('<div class="ce-block ce-block--text"><p>a</p>\n<p>b</p></div>'),
            { within: WITHIN },
        );
        expect(wrapped.text).toBe(unwrapped.text);
        expect(wrapped.text).toBe('a\nb');
    });
});
