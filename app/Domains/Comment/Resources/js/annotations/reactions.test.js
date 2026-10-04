import { describe, it, expect, beforeEach } from 'vitest';
import { annotationReactions } from './reactions.js';
import * as drafts from '../comment-draft/index.js';

const USER_ID = 7;
const CHAPTER_ID = 42;

function stored() {
    const raw = localStorage.getItem(`comment-drafts:${USER_ID}:chapter:${CHAPTER_ID}`);
    return raw ? JSON.parse(raw) : { annotations: [], annotationChanges: { adds: [] } };
}

/** Chapter page: the annotable region wraps the article; the toolbar lives in <body>. */
function chapter(articleHtml, mode = 'draft') {
    document.body.innerHTML = `
        <div data-annotable data-entity-type="chapter" data-entity-id="${CHAPTER_ID}" data-annotation-mode="${mode}">
            <article data-quote-article>${articleHtml}</article>
        </div>`;
}

function makeComponent() {
    const el = document.createElement('div');
    el.dataset.userId = String(USER_ID);
    el.dataset.highlightMaxLength = '500';
    document.body.appendChild(el);

    const component = annotationReactions();
    component.$root = el;
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

const ONE_BLOCK = '<div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>';

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
    window.getSelection()?.removeAllRanges();
    window.commentDrafts = drafts;
});

describe('annotationReactions.react', () => {
    it('writes <p>❤️</p> as a draft in draft mode and as a pending add in pending mode', () => {
        chapter(ONE_BLOCK, 'draft');
        select(textOf('#a'), 3, textOf('#a'), 12);
        makeComponent().react('❤️');

        expect(stored().annotations).toHaveLength(1);
        expect(stored().annotations[0].body).toBe('<p>❤️</p>');
        expect(stored().annotationChanges.adds).toEqual([]);

        localStorage.clear();
        chapter(ONE_BLOCK, 'pending');
        select(textOf('#a'), 3, textOf('#a'), 12);
        makeComponent().react('🔥');

        expect(stored().annotations).toEqual([]);
        expect(stored().annotationChanges.adds).toHaveLength(1);
        expect(stored().annotationChanges.adds[0].body).toBe('<p>🔥</p>');
    });

    it('stores the anchor (highlighted, prefix, suffix) of the selection', () => {
        chapter(ONE_BLOCK, 'pending');
        select(textOf('#a'), 3, textOf('#a'), 12);

        makeComponent().react('👍');

        expect(stored().annotationChanges.adds[0]).toMatchObject({
            body: '<p>👍</p>',
            highlighted: 'chat dort',
            prefix: 'le',
            suffix: 'sur le tapis',
        });
    });

    it('ignores a selection spanning two blocks and one over the highlight cap', () => {
        chapter(`
            <div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>
            <div class="ce-block ce-block--text"><p id="b">le chien court dans le jardin</p></div>
            <div class="ce-block ce-block--text"><p id="c">${'x'.repeat(501)}</p></div>`);

        select(textOf('#a'), 3, textOf('#b'), 8);
        makeComponent().react('❤️');
        select(textOf('#c'), 0, textOf('#c'), 501);
        makeComponent().react('❤️');

        expect(stored().annotations).toEqual([]);
        expect(stored().annotationChanges.adds).toEqual([]);
    });

    it('clears the selection after reacting', () => {
        chapter(ONE_BLOCK);
        select(textOf('#a'), 3, textOf('#a'), 12);

        makeComponent().react('❤️');

        expect(window.getSelection().isCollapsed).toBe(true);
    });
});
