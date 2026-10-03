import { describe, it, expect, beforeEach } from 'vitest';
import { selectionIsWithin, showToolbar } from './toolbar.js';

const DECLARING = '<button data-action="declaring" data-requires-selection-within=".ok">Declaring</button>';
const PLAIN = '<button data-action="plain">Plain</button>';

function setup(actions = DECLARING + PLAIN, content = null) {
    document.body.innerHTML = `
        <div class="annotable-region" data-annotable data-entity-type="chapter" data-entity-id="1"
             data-can-annotate="true" data-max-selection="500">
            <template id="comment-toolbar-template">
                <div class="comment-toolbar">
                    <div data-toolbar-actions>${actions}</div>
                    <span data-toolbar-too-long class="hidden">Too long</span>
                </div>
            </template>
            <div id="content">${content ?? `
                <div class="ok"><p id="a">a</p></div>
                <figure><figcaption id="c">c</figcaption></figure>
                <div class="ok"><p id="b">b</p></div>
            `}</div>
        </div>`;
}

function textOf(id) {
    return document.getElementById(id).firstChild;
}

function range(startNode, startOffset, endNode, endOffset) {
    const r = document.createRange();
    r.setStart(startNode, startOffset);
    r.setEnd(endNode, endOffset);
    return r;
}

function select(r) {
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(r);
}

function toolbar() {
    return document.getElementById('comment-toolbar-active');
}

function action(name) {
    return toolbar().querySelector(`[data-action="${name}"]`);
}

beforeEach(() => {
    window.getSelection()?.removeAllRanges();
    toolbar()?.remove();
    document.body.innerHTML = '';
});

describe('selectionIsWithin', () => {
    it('is true for a range inside one matching area', () => {
        setup();
        expect(selectionIsWithin(range(textOf('a'), 0, textOf('a'), 1), '.ok')).toBe(true);
    });

    it('is true for a range spanning two matching areas when only whitespace sits between them', () => {
        setup(DECLARING, '<div class="ok"><p id="a">a</p></div>\n  <div class="ok"><p id="b">b</p></div>');
        expect(selectionIsWithin(range(textOf('a'), 0, textOf('b'), 1), '.ok')).toBe(true);
    });

    it('is false when the range is wholly inside a caption', () => {
        setup();
        expect(selectionIsWithin(range(textOf('c'), 0, textOf('c'), 1), '.ok')).toBe(false);
    });

    it('is false when the range runs from a matching area into a caption', () => {
        setup();
        expect(selectionIsWithin(range(textOf('a'), 0, textOf('c'), 1), '.ok')).toBe(false);
    });

    it('is true when the range ends at the start of the caption block without covering it (triple-click)', () => {
        setup();
        const figure = document.querySelector('figure');
        expect(selectionIsWithin(range(textOf('a'), 0, figure, 0), '.ok')).toBe(true);
    });

    it('is true when the range starts at the very end of the caption text', () => {
        setup();
        expect(selectionIsWithin(range(textOf('c'), 1, textOf('b'), 1), '.ok')).toBe(true);
    });
});

describe('showToolbar', () => {
    it('shows the declaring action for a selection inside a matching area', () => {
        setup();
        select(range(textOf('a'), 0, textOf('a'), 1));
        showToolbar();

        expect(toolbar().style.display).toBe('');
        expect(action('declaring').style.display).toBe('');
        expect(action('plain').style.display).toBe('');
    });

    it('hides the declaring action but keeps the plain action when the selection touches a caption', () => {
        setup();
        select(range(textOf('a'), 0, textOf('c'), 1));
        showToolbar();

        expect(toolbar().style.display).toBe('');
        expect(action('declaring').style.display).toBe('none');
        expect(action('plain').style.display).toBe('');
    });

    it('does not show the toolbar when no action is applicable', () => {
        setup(DECLARING);
        select(range(textOf('c'), 0, textOf('c'), 1));
        showToolbar();

        expect(toolbar().style.display).toBe('none');
    });

    it('always shows an action without the attribute', () => {
        setup(PLAIN);
        select(range(textOf('c'), 0, textOf('c'), 1));
        showToolbar();

        expect(toolbar().style.display).toBe('');
        expect(action('plain').style.display).toBe('');
    });

    it('re-shows a previously hidden action on the next applicable selection', () => {
        setup();
        select(range(textOf('c'), 0, textOf('c'), 1));
        showToolbar();
        expect(action('declaring').style.display).toBe('none');

        select(range(textOf('b'), 0, textOf('b'), 1));
        showToolbar();
        expect(action('declaring').style.display).toBe('');
    });

    it('an annotate button is hidden like the quote button when the selection touches an image caption', () => {
        const quote = '<button data-action="quote" data-requires-selection-within=".ce-block--text">Citer</button>';
        const annotate = '<button data-action="annotate" data-requires-selection-within=".ce-block--text">Annoter</button>';
        setup(quote + annotate, `
            <div class="ce-block ce-block--text"><p id="a">a</p></div>
            <figure class="ce-block ce-block--image"><figcaption id="c">c</figcaption></figure>`);

        select(range(textOf('a'), 0, textOf('a'), 1));
        showToolbar();
        expect(action('quote').style.display).toBe('');
        expect(action('annotate').style.display).toBe('');

        select(range(textOf('a'), 0, textOf('c'), 1));
        showToolbar();
        expect(toolbar().style.display).toBe('none');
        expect(action('quote').style.display).toBe('none');
        expect(action('annotate').style.display).toBe('none');
    });

    it('applies the too-long state only when an action is applicable', () => {
        const long = 'x'.repeat(600);
        setup(DECLARING, `<div class="ok"><p id="a">${long}</p></div><figure><figcaption id="c">${long}</figcaption></figure>`);

        select(range(textOf('c'), 0, textOf('c'), 600));
        showToolbar();
        expect(toolbar().style.display).toBe('none');
        expect(toolbar().querySelector('[data-toolbar-too-long]').classList.contains('hidden')).toBe(true);

        select(range(textOf('a'), 0, textOf('a'), 600));
        showToolbar();
        expect(toolbar().style.display).toBe('');
        expect(toolbar().querySelector('[data-toolbar-too-long]').classList.contains('hidden')).toBe(false);
    });
});
