import { describe, it, expect, beforeEach, vi } from 'vitest';
import { resolveRows, QUOTABLE_AREA_SELECTOR } from './author-anchoring.js';
import { quoteMiniForm } from './mini-form.js';
import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';

vi.mock('../api/client.js', () => ({ createQuote: vi.fn() }));

/** Text block / image block with caption / text block, as an Advanced chapter renders it. */
const ILLUSTRATED = `
    <article data-quote-article>
        <div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>
        <figure class="ce-block ce-block--image media-image"><img alt=""><figcaption id="cap">une légende sous image</figcaption></figure>
        <div class="ce-block ce-block--text"><p id="b">le chien court dans le jardin</p></div>
    </article>`;

function capture(node, start, end) {
    const range = document.createRange();
    range.setStart(node, start);
    range.setEnd(node, end);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);

    const component = quoteMiniForm();
    component.$el = document.createElement('div');
    component.$el.dataset.highlightMaxLength = '1000';
    component.openForm({ chapterId: 1, storyId: 2 });

    return component._anchor;
}

function row(anchor) {
    return { id: 1, highlighted_text: anchor.highlighted, prefix: anchor.prefix, suffix: anchor.suffix };
}

/** Map a canonical offset back to the (text node, offset) it came from. */
function domPoint(articleEl, offset) {
    const { nodeMap } = buildCanonicalText(articleEl, { within: QUOTABLE_AREA_SELECTOR });
    const entry = nodeMap.find(e => offset >= e.start && offset <= e.end);
    return entry ? { node: entry.domNode, offset: offset - entry.start } : null;
}

beforeEach(() => {
    document.body.innerHTML = '';
});

describe('resolveRows — quotable areas', () => {
    it('re-anchors a quote captured by the mini-form on an illustrated chapter', () => {
        document.body.innerHTML = ILLUSTRATED;
        const textB = document.querySelector('#b').firstChild;
        const anchor = capture(textB, 3, 14);
        expect(anchor).not.toBeNull();

        const articleEl = document.querySelector('[data-quote-article]');
        const [resolved] = resolveRows([row(anchor)], articleEl);

        expect(resolved.range).not.toBeNull();
        expect(domPoint(articleEl, resolved.range.start)).toEqual({ node: textB, offset: 3 });
        expect(domPoint(articleEl, resolved.range.end)).toEqual({ node: textB, offset: 14 });
    });

    it('does not resolve a stored quote whose highlighted text only exists in a caption', () => {
        document.body.innerHTML = ILLUSTRATED;
        const articleEl = document.querySelector('[data-quote-article]');

        const [resolved] = resolveRows(
            [{ id: 1, highlighted_text: 'une légende', prefix: '', suffix: 'sous image' }],
            articleEl,
        );

        expect(resolved.range).toBeNull();
    });
});
