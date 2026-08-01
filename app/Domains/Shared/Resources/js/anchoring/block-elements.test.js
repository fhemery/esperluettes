import { describe, it, expect } from 'vitest';
import { isBlockElement, closestBlock } from './block-elements.js';

function makeEl(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div;
}

describe('isBlockElement', () => {
    it('accepts real block tags', () => {
        for (const html of ['<p>a</p>', '<blockquote>a</blockquote>', '<h1>a</h1>', '<h6>a</h6>', '<pre>a</pre>']) {
            expect(isBlockElement(makeEl(html).firstElementChild)).toBe(true);
        }
        expect(isBlockElement(makeEl('<ul><li>a</li></ul>').querySelector('li'))).toBe(true);
    });

    it('accepts an editor block wrapper div', () => {
        expect(isBlockElement(makeEl('<div class="ce-block ce-block--text"><p>a</p></div>').firstElementChild)).toBe(true);
    });

    it('rejects a decorative div', () => {
        expect(isBlockElement(makeEl('<div class="wrapper">a</div>').firstElementChild)).toBe(false);
    });

    it('rejects inline elements', () => {
        expect(isBlockElement(makeEl('<em>a</em>').firstElementChild)).toBe(false);
        expect(isBlockElement(makeEl('<strong>a</strong>').firstElementChild)).toBe(false);
    });

    it('rejects text nodes and nullish input', () => {
        expect(isBlockElement(makeEl('<p>a</p>').firstElementChild.firstChild)).toBe(false);
        expect(isBlockElement(null)).toBe(false);
    });
});

describe('closestBlock', () => {
    it('finds the paragraph of a text node nested in inline markup', () => {
        const root = makeEl('<p id="one">le <em>chat</em> dort</p>');
        const em = root.querySelector('em');

        expect(closestBlock(em.firstChild)).toBe(root.querySelector('#one'));
    });

    it('skips a decorative div and returns the editor block', () => {
        const root = makeEl('<div class="ce-block"><div class="inner">texte</div></div>');
        const inner = root.querySelector('.inner');

        expect(closestBlock(inner.firstChild)).toBe(root.querySelector('.ce-block'));
    });

    it('returns null when there is no block ancestor', () => {
        const root = makeEl('<span>texte</span>');

        expect(closestBlock(root.querySelector('span').firstChild)).toBeNull();
    });
});
