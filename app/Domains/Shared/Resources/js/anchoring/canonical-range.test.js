import { describe, it, expect } from 'vitest';
import { buildCanonicalText } from './canonical-text.js';
import { findAnchor } from './reanchor.js';
import { rangeFromCanonical } from './canonical-range.js';

function canonicalOf(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div);
    return { el: div, canonical: buildCanonicalText(div) };
}

describe('rangeFromCanonical', () => {
    it('maps offsets inside a single text node', () => {
        const { canonical } = canonicalOf('<p>Hello brave world</p>');
        const range = rangeFromCanonical(canonical, 6, 11);

        expect(range).not.toBeNull();
        expect(range.startContainer).toBe(canonical.nodeMap[0].domNode);
        expect(range.startOffset).toBe(6);
        expect(range.endContainer).toBe(canonical.nodeMap[0].domNode);
        expect(range.endOffset).toBe(11);
        expect(range.toString()).toBe('brave');
    });

    it('spans two text nodes inside one paragraph', () => {
        const { canonical } = canonicalOf('<p>Hello <strong>bold</strong> text</p>');
        // "Hello bold text": "lo bold te" = [3, 13)
        const range = rangeFromCanonical(canonical, 3, 13);

        expect(range.startContainer).toBe(canonical.nodeMap[0].domNode);
        expect(range.endContainer).toBe(canonical.nodeMap[2].domNode);
        expect(range.toString()).toBe(canonical.text.slice(3, 13));
    });

    it('spans two paragraphs, with offsets on the block newline', () => {
        const { canonical } = canonicalOf('<p>First</p>\n<p>Second</p>');
        expect(canonical.text).toBe('First\nSecond');

        // Starts on the "\n" (5): snaps forward to the start of "Second".
        const fromNewline = rangeFromCanonical(canonical, 5, 9);
        expect(fromNewline.startContainer).toBe(canonical.nodeMap[1].domNode);
        expect(fromNewline.startOffset).toBe(0);
        expect(fromNewline.toString()).toBe('Sec');

        // Ends right after the "\n" (6): snaps back to the end of "First".
        const toNewline = rangeFromCanonical(canonical, 2, 6);
        expect(toNewline.endContainer).toBe(canonical.nodeMap[0].domNode);
        expect(toNewline.endOffset).toBe(5);
        expect(toNewline.toString()).toBe('rst');

        // Across the boundary: the range covers the formatting "\n" text node
        // stored between the paragraphs, which happens to read like the
        // canonical newline.
        const across = rangeFromCanonical(canonical, 2, 9);
        expect(across.startContainer).toBe(canonical.nodeMap[0].domNode);
        expect(across.endContainer).toBe(canonical.nodeMap[1].domNode);
        expect(across.toString()).toBe(canonical.text.slice(2, 9));
    });

    it('spans adjacent paragraphs: the range text is the slice minus the boundary newline', () => {
        const { canonical } = canonicalOf('<p>First</p><p>Second</p>');
        const range = rangeFromCanonical(canonical, 2, 9);
        expect(range.toString()).toBe(canonical.text.slice(2, 9).replace('\n', ''));
    });

    it('snaps a start on an emoji token to the next text node', () => {
        const { canonical } = canonicalOf('<p>Love <span class="ql-custom-emoji-heart"></span> you</p>');
        expect(canonical.text).toBe('Love :heart: you');

        // ":heart: you" = [5, 16)
        const range = rangeFromCanonical(canonical, 5, 16);
        expect(range.startContainer).toBe(canonical.nodeMap[1].domNode);
        expect(range.startOffset).toBe(0);
        expect(range.toString()).toBe(' you');
    });

    it('snaps an end on an emoji token to the previous text node', () => {
        const { canonical } = canonicalOf('<p>Love <span class="ql-custom-emoji-heart"></span> you</p>');

        // "Love :heart:" = [0, 12)
        const range = rangeFromCanonical(canonical, 0, 12);
        expect(range.endContainer).toBe(canonical.nodeMap[0].domNode);
        expect(range.endOffset).toBe(5);
        expect(range.toString()).toBe('Love ');
    });

    it('returns null when the offsets cover only an emoji token or a newline', () => {
        const emoji = canonicalOf('<p>Love <span class="ql-custom-emoji-heart"></span> you</p>').canonical;
        expect(rangeFromCanonical(emoji, 5, 12)).toBeNull();

        const blocks = canonicalOf('<p>First</p><p>Second</p>').canonical;
        expect(rangeFromCanonical(blocks, 5, 6)).toBeNull();
    });

    it('returns null on an empty or inverted span', () => {
        const { canonical } = canonicalOf('<p>Hello world</p>');
        expect(rangeFromCanonical(canonical, 3, 3)).toBeNull();
        expect(rangeFromCanonical(canonical, 5, 2)).toBeNull();
    });

    it('returns null when the offsets are out of bounds', () => {
        const { canonical } = canonicalOf('<p>Hello world</p>');
        expect(rangeFromCanonical(canonical, 50, 60)).toBeNull();
        expect(rangeFromCanonical({ text: '', nodeMap: [] }, 0, 4)).toBeNull();
    });

    it('clamps a span that overruns the text', () => {
        const { canonical } = canonicalOf('<p>Hello world</p>');
        expect(rangeFromCanonical(canonical, 6, 99).toString()).toBe('world');
    });

    it('does not mutate the DOM', () => {
        const { el, canonical } = canonicalOf('<p>Hello <em>there</em> world</p>');
        const before = el.innerHTML;
        rangeFromCanonical(canonical, 2, 14);
        expect(el.innerHTML).toBe(before);
    });

    it('round-trips a findAnchor result back to the highlighted passage', () => {
        const { canonical } = canonicalOf(
            '<div class="ce-block--text"><p>Il était une fois une <strong>princesse</strong> endormie.</p>'
            + '<p>Elle rêvait <span class="ql-custom-emoji-star"></span> de dragons.</p></div>'
        );
        const anchor = {
            prefix: 'une fois une',
            highlighted: 'princesse endormie.\nElle rêvait',
            suffix: ':star: de dragons.',
        };

        const found = findAnchor(canonical, anchor);
        expect(found.status).toBe('ok');

        const range = rangeFromCanonical(canonical, found.start, found.end);
        expect(range.toString()).toContain('princesse endormie.');
        expect(range.toString()).toContain('Elle rêvait');
        expect(range.toString()).toBe(anchor.highlighted.replace('\n', ''));
    });
});
