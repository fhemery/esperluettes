import { describe, it, expect, beforeEach } from 'vitest';
import { quoteAuthorHeat } from './author-heat.js';

/**
 * The component is exercised without Alpine: `_render()` only needs the article
 * element, which is what the risky part (canonical segment → DOM marks) works on.
 */
function heatOver(html) {
    document.body.innerHTML = `<div><article data-quote-article>${html}</article></div>`;
    const component = quoteAuthorHeat({
        markerLabelOne: '{count} citation',
        markerLabelOther: '{count} citations',
        tintLabelOne: 'cité par {count} lecteurice',
        tintLabelOther: 'cité par {count} lecteurices',
    });
    component._articleEl = document.querySelector('[data-quote-article]');
    component._gutterEl = null;
    return component;
}

function row(id, highlighted, prefix = '', suffix = '', createdAt = '2026-01-0' + id + 'T10:00:00Z') {
    return { id, highlighted_text: highlighted, prefix, suffix, created_at: createdAt, quoter: { user_id: id } };
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

describe('author heat — passage popover', () => {
    let opened;

    function listen() {
        opened = [];
        const handler = event => opened.push(event.detail);
        window.addEventListener('quote:open-author-panel', handler);
        return handler;
    }

    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('lists every quote covering the clicked point', () => {
        const handler = listen();
        const heat = heatOver('<div class="ce-block"><p>le chat dort sur le toit</p></div>');

        heat._render([
            row(1, 'le chat dort'),
            row(2, 'chat dort sur'),
            row(3, 'chat'),
            row(4, 'sur le toit'),
        ]);

        // The deepest segment is « chat », covered by quotes 1, 2 and 3.
        const deepest = marks().find(m => m.dataset.quoteDepth === '3');
        deepest.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        // Newest first (A4): created_at descends with the id in the fixture.
        expect(opened).toHaveLength(1);
        expect(opened[0].quotes.map(q => q.id)).toEqual([3, 2, 1]);

        window.removeEventListener('quote:open-author-panel', handler);
    });

    it('opens the panel on Enter from a focused tint', () => {
        const handler = listen();
        const heat = heatOver('<div class="ce-block"><p>le chat dort sur le toit</p></div>');

        heat._render([row(1, 'chat dort')]);

        const mark = marks()[0];
        expect(mark.getAttribute('tabindex')).toBe('0');
        expect(mark.getAttribute('aria-label')).toBe('cité par 1 lecteurice');

        mark.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));

        expect(opened).toHaveLength(1);
        expect(opened[0].quotes.map(q => q.id)).toEqual([1]);

        window.removeEventListener('quote:open-author-panel', handler);
    });

    it('scrolls to a passage focused from the summary and opens its popover', () => {
        const handler = listen();
        const heat = heatOver('<div class="ce-block"><p>le chat dort sur le toit</p></div>');

        heat._render([row(1, 'chat dort'), row(2, 'chat dort')]);

        const scrolled = [];
        marks().forEach(mark => { mark.scrollIntoView = () => scrolled.push(mark.textContent); });

        heat.focusGroup('chat dort');

        expect(scrolled).toEqual(['chat dort']);
        expect(opened).toHaveLength(1);
        expect(opened[0].quotes.map(q => q.id)).toEqual([2, 1]);

        window.removeEventListener('quote:open-author-panel', handler);
    });

    it('does nothing when the focused passage is stale', () => {
        const handler = listen();
        const heat = heatOver('<div class="ce-block"><p>le chat dort</p></div>');

        heat._render([row(1, 'le chien aboie')]);
        heat.focusGroup('le chien aboie');

        expect(opened).toHaveLength(0);

        window.removeEventListener('quote:open-author-panel', handler);
    });
});
