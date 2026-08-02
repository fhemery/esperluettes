import { describe, it, expect, beforeEach, vi } from 'vitest';
import { quoteMiniForm } from './mini-form.js';
import { createQuote } from '../api/client.js';

vi.mock('../api/client.js', () => ({ createQuote: vi.fn() }));

const TOO_LONG = 'trop long';
const MULTI_BLOCK = 'plusieurs blocs';

function makeComponent() {
    const el = document.createElement('div');
    el.dataset.highlightMaxLength = '1000';
    el.dataset.errorHighlightTooLong = TOO_LONG;
    el.dataset.errorHighlightMultiBlock = MULTI_BLOCK;

    const component = quoteMiniForm();
    component.$el = el;

    return component;
}

function select(startNode, startOffset, endNode, endOffset) {
    const range = document.createRange();
    range.setStart(startNode, startOffset);
    range.setEnd(endNode, endOffset);

    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
}

function textOf(selector) {
    return document.querySelector(selector).firstChild;
}

beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(createQuote).mockReset();
});

describe('quoteMiniForm.openForm — multi-block guard', () => {
    it('accepts a selection inside a single paragraph', () => {
        document.body.innerHTML = `
            <div class="annotable-region">
                <p id="a">le chat dort sur le tapis</p>
                <p id="b">le chien court dans le jardin</p>
            </div>`;
        select(textOf('#a'), 0, textOf('#a'), 12);

        const component = makeComponent();
        component.openForm({ chapterId: 1, storyId: 2 });

        expect(component.open).toBe(true);
        expect(component.multiBlock).toBe(false);
        expect(component.error).toBeNull();
    });

    it('accepts a selection spanning inline markup inside one paragraph', () => {
        document.body.innerHTML = `
            <div class="annotable-region">
                <p id="a">le <em id="e">chat</em> dort</p>
            </div>`;
        select(textOf('#a'), 0, textOf('#e'), 4);

        const component = makeComponent();
        component.openForm({ chapterId: 1, storyId: 2 });

        expect(component.open).toBe(true);
        expect(component.multiBlock).toBe(false);
        expect(component.error).toBeNull();
    });

    it('rejects a selection spanning two paragraphs', () => {
        document.body.innerHTML = `
            <div class="annotable-region">
                <p id="a">le chat dort</p>
                <p id="b">le chien court</p>
            </div>`;
        select(textOf('#a'), 0, textOf('#b'), 8);

        const component = makeComponent();
        component.openForm({ chapterId: 1, storyId: 2 });

        expect(component.open).toBe(true);
        expect(component.multiBlock).toBe(true);
        expect(component.error).toBe(MULTI_BLOCK);
    });

    it('rejects a selection spanning two editor block wrappers', () => {
        document.body.innerHTML = `
            <div class="annotable-region">
                <div class="ce-block ce-block--text"><p id="a">le chat dort</p></div>
                <div class="ce-block ce-block--text"><p id="b">le chien court</p></div>
            </div>`;
        select(textOf('#a'), 0, textOf('#b'), 8);

        const component = makeComponent();
        component.openForm({ chapterId: 1, storyId: 2 });

        expect(component.multiBlock).toBe(true);
        expect(component.error).toBe(MULTI_BLOCK);
    });

    it('does not save while the selection spans several blocks', async () => {
        document.body.innerHTML = `
            <div class="annotable-region">
                <p id="a">le chat dort</p>
                <p id="b">le chien court</p>
            </div>`;
        select(textOf('#a'), 0, textOf('#b'), 8);

        const component = makeComponent();
        component.openForm({ chapterId: 1, storyId: 2 });
        await component.save();

        expect(createQuote).not.toHaveBeenCalled();
        expect(component.open).toBe(true);
        expect(component.saving).toBe(false);
    });
});
