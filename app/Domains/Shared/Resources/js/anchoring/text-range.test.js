import { describe, it, expect, beforeEach } from 'vitest';
import { coveredTextSlices, trimRangeToText } from './text-range.js';

/** Text block / image block with caption / text block, as an Advanced chapter renders it. */
beforeEach(() => {
    document.body.innerHTML = `
        <article>
            <div class="ce-block ce-block--text"><p id="a">le chat dort</p></div>
            <figure id="fig" class="ce-block ce-block--image"><img alt=""><figcaption id="cap">une légende</figcaption></figure>
            <div class="ce-block ce-block--text"><p id="b">le chien court</p></div>
        </article>`;
});

function textOf(id) {
    return document.getElementById(id).firstChild;
}

function range(startNode, startOffset, endNode, endOffset) {
    return { startContainer: startNode, startOffset, endContainer: endNode, endOffset };
}

describe('coveredTextSlices', () => {
    it('lists only the text a range really covers', () => {
        const slices = coveredTextSlices(range(textOf('a'), 3, textOf('cap'), 3));

        expect(slices.map(s => s.node.data.slice(s.start, s.end))).toEqual(['chat dort', 'une']);
    });

    it('ignores an end boundary at the start of a block (triple-click before an image)', () => {
        const figure = document.getElementById('fig');
        const slices = coveredTextSlices(range(textOf('a'), 0, figure, 0));

        expect(slices.map(s => s.node)).toEqual([textOf('a')]);
    });

    it('ignores a start boundary at the very end of a caption', () => {
        const cap = textOf('cap');
        const slices = coveredTextSlices(range(cap, cap.length, textOf('b'), 2));

        expect(slices.map(s => s.node)).toEqual([textOf('b')]);
    });
});

describe('trimRangeToText', () => {
    it('moves an element end boundary back to the last covered text', () => {
        const figure = document.getElementById('fig');
        const trimmed = trimRangeToText(range(textOf('a'), 0, figure, 0));

        expect(trimmed).toEqual(range(textOf('a'), 0, textOf('a'), textOf('a').length));
    });

    it('moves a start boundary sitting at the end of a caption to the next covered text', () => {
        const cap = textOf('cap');
        const trimmed = trimRangeToText(range(cap, cap.length, textOf('b'), 2));

        expect(trimmed).toEqual(range(textOf('b'), 0, textOf('b'), 2));
    });

    it('leaves a range between two text offsets unchanged', () => {
        const trimmed = trimRangeToText(range(textOf('a'), 3, textOf('a'), 7));

        expect(trimmed).toEqual(range(textOf('a'), 3, textOf('a'), 7));
    });

    it('is null when the range covers no text', () => {
        const figure = document.getElementById('fig');

        expect(trimRangeToText(range(textOf('a'), textOf('a').length, figure, 0))).toBeNull();
    });
});
