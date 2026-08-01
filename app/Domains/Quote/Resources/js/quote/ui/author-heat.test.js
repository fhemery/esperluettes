import { describe, it, expect, beforeEach } from 'vitest';
import { quoteAuthorHeat } from './author-heat.js';

/**
 * The component is exercised without Alpine: `_render()` only needs the article
 * element, which is what the risky part (canonical segment → DOM marks) works on.
 */
function heatOver(html) {
    document.body.innerHTML = `<div><article data-quote-article>${html}</article></div>`;
    const component = quoteAuthorHeat({ markerLabelOne: '{count} citation', markerLabelOther: '{count} citations' });
    component._articleEl = document.querySelector('[data-quote-article]');
    component._gutterEl = null;
    return component;
}

function row(id, highlighted, prefix = '', suffix = '') {
    return { id, highlighted_text: highlighted, prefix, suffix, quoter: { user_id: id } };
}

function marks() {
    return Array.from(document.querySelectorAll('mark.quote-heat'));
}

describe('author heat — tint', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('wraps a quoted passage in a single mark', () => {
        const heat = heatOver('<div class="ce-block"><p>le chat dort sur le toit</p></div>');

        heat._render([row(1, 'chat dort')]);

        expect(marks().map(m => m.textContent)).toEqual(['chat dort']);
        expect(marks()[0].dataset.quoteDepth).toBe('1');
    });

    it('deepens the tint where two quotes overlap', () => {
        const heat = heatOver('<div class="ce-block"><p>le chat dort sur le toit</p></div>');

        heat._render([row(1, 'le chat dort'), row(2, 'chat dort sur')]);

        expect(marks().map(m => [m.textContent, m.dataset.quoteDepth])).toEqual([
            ['le ', '1'],
            ['chat dort', '2'],
            [' sur', '1'],
        ]);
    });

    it('splits one canonical segment into one mark per block it covers', () => {
        const heat = heatOver(
            '<div class="ce-block"><p>premier bloc</p></div><div class="ce-block"><p>second bloc</p></div>'
        );

        // Such a passage can no longer be captured, but if one exists it must
        // yield one mark per text node it covers, never a throw.
        heat._render([row(1, 'bloc second')]);

        expect(marks().map(m => m.textContent)).toEqual(['bloc', 'second']);
    });

    it('leaves the text untouched for a passage that no longer exists', () => {
        const heat = heatOver('<div class="ce-block"><p>le chat dort</p></div>');

        heat._render([row(1, 'le chien aboie')]);

        expect(marks()).toHaveLength(0);
    });

    it('removes every mark when the heat is turned off', () => {
        const heat = heatOver('<div class="ce-block"><p>le chat dort sur le toit</p></div>');

        heat._render([row(1, 'le chat dort'), row(2, 'chat dort sur')]);
        heat._render([]);

        expect(marks()).toHaveLength(0);
        expect(document.querySelector('[data-quote-article]').textContent).toBe('le chat dort sur le toit');
    });
});
