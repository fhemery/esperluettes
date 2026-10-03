import { describe, it, expect, beforeEach, vi } from 'vitest';
import { annotationForm } from './capture-form.js';
import * as drafts from '../comment-draft/index.js';

const USER_ID = 7;
const CHAPTER_ID = 42;
const BLANK = 'vide';
const BODY_TOO_LONG = 'note trop longue';
const TOO_LONG = 'trop long';
const MULTI_BLOCK = 'plusieurs blocs';

function storageKey() {
    return `comment-drafts:${USER_ID}:chapter:${CHAPTER_ID}`;
}

function storedAnnotations() {
    const raw = localStorage.getItem(storageKey());
    return raw ? JSON.parse(raw).annotations : [];
}

function makeComponent() {
    const el = document.createElement('div');
    el.dataset.userId = String(USER_ID);
    el.dataset.entityType = 'chapter';
    el.dataset.entityId = String(CHAPTER_ID);
    el.dataset.highlightMaxLength = '500';
    el.dataset.bodyMaxLength = '1000';
    el.dataset.errorBlank = BLANK;
    el.dataset.errorBodyTooLong = BODY_TOO_LONG;
    el.dataset.errorHighlightTooLong = TOO_LONG;
    el.dataset.errorHighlightMultiBlock = MULTI_BLOCK;
    document.body.appendChild(el);

    const component = annotationForm();
    // Alpine: $root is the x-data element; $el is whichever element fired the
    // directive (the root for window listeners, a button for @click).
    component.$root = el;
    component.$el = el;

    return component;
}

/** Hidden textarea the rich-text editor syncs its HTML into. */
function editorTextarea() {
    return document.getElementById('quill-editor-area-annotation-body-editor');
}

function typeBody(html) {
    editorTextarea().value = html;
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

function chapter(articleHtml) {
    document.body.innerHTML = `
        <article data-quote-article>${articleHtml}</article>
        <textarea id="quill-editor-area-annotation-body-editor"></textarea>`;
}

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
    window.getSelection()?.removeAllRanges();
    window.initQuillEditor = vi.fn();
    window.commentDrafts = drafts;
});

describe('annotationForm.openForm', () => {
    it('extracts highlighted text, prefix and suffix from a selection inside one text block', () => {
        chapter('<div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>');
        select(textOf('#a'), 3, textOf('#a'), 12);

        const component = makeComponent();
        component.openForm();

        expect(component.open).toBe(true);
        expect(component.error).toBeNull();
        expect(component.highlighted).toBe('chat dort');
        expect(component._anchor.prefix).toBe('le');
        expect(component._anchor.suffix).toBe('sur le tapis');
        expect(window.initQuillEditor).toHaveBeenCalledWith('annotation-body-editor');
        expect(window.getSelection().isCollapsed).toBe(true);
    });

    it('refuses a selection spanning two .ce-block--text blocks with the multi-block error', () => {
        chapter(`
            <div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>
            <div class="ce-block ce-block--text"><p id="b">le chien court dans le jardin</p></div>`);
        select(textOf('#a'), 3, textOf('#b'), 8);

        const component = makeComponent();
        component.openForm();

        expect(component.open).toBe(true);
        expect(component.multiBlock).toBe(true);
        expect(component.error).toBe(MULTI_BLOCK);
        expect(component.canSave).toBe(false);

        typeBody('<p>une note</p>');
        component.save();
        expect(storedAnnotations()).toEqual([]);
    });

    it('refuses a highlight over 500 characters', () => {
        chapter(`<div class="ce-block ce-block--text"><p id="a">${'x'.repeat(501)}</p></div>`);
        select(textOf('#a'), 0, textOf('#a'), 501);

        const component = makeComponent();
        component.openForm();

        expect(component.tooLong).toBe(true);
        expect(component.error).toBe(TOO_LONG);
        expect(component.canSave).toBe(false);

        typeBody('<p>une note</p>');
        component.save();
        expect(storedAnnotations()).toEqual([]);
    });
});

describe('annotationForm.save', () => {
    function openOnChat() {
        chapter('<div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>');
        select(textOf('#a'), 3, textOf('#a'), 12);
        const component = makeComponent();
        component.openForm();
        return component;
    }

    it('refuses a blank body', () => {
        const component = openOnChat();
        typeBody('<p>  &nbsp; </p>');

        component.save();

        expect(component.open).toBe(true);
        expect(component.error).toBe(BLANK);
        expect(storedAnnotations()).toEqual([]);
    });

    it('refuses a body over the maximum length', () => {
        const component = openOnChat();
        typeBody(`<p>${'y'.repeat(1001)}</p>`);

        component.save();

        expect(component.open).toBe(true);
        expect(component.error).toBe(BODY_TOO_LONG);
        expect(storedAnnotations()).toEqual([]);
    });

    it('save stores a draft through commentDrafts.addAnnotation and closes', () => {
        const component = openOnChat();
        const spy = vi.spyOn(window.commentDrafts, 'addAnnotation');
        typeBody('<p>une <strong>note</strong></p>');

        component.save();

        expect(spy).toHaveBeenCalledWith(USER_ID, 'chapter', String(CHAPTER_ID), {
            body: '<p>une <strong>note</strong></p>',
            highlighted: 'chat dort',
            prefix: 'le',
            suffix: 'sur le tapis',
        });
        expect(component.open).toBe(false);
        const stored = storedAnnotations();
        expect(stored).toHaveLength(1);
        expect(stored[0]).toMatchObject({ body: '<p>une <strong>note</strong></p>', highlighted: 'chat dort' });
        spy.mockRestore();
    });

    it('save triggered from the Enregistrer button ($el is the button) stores the draft', () => {
        const component = openOnChat();
        const button = document.createElement('button');
        component.$root.appendChild(button);
        component.$el = button;
        typeBody('<p>une note</p>');

        component.save();

        expect(storedAnnotations()).toHaveLength(1);
        expect(component.open).toBe(false);
    });

    it('a blank body saved from the Enregistrer button shows the blank error', () => {
        const component = openOnChat();
        const button = document.createElement('button');
        component.$root.appendChild(button);
        component.$el = button;
        typeBody('<p> </p>');

        component.save();

        expect(component.open).toBe(true);
        expect(component.error).toBe(BLANK);
    });

    it('Ctrl+Enter saves; blur does not', () => {
        const component = openOnChat();
        typeBody('<p>une note</p>');

        component.$el.dispatchEvent(new FocusEvent('focusout'));
        component.$el.dispatchEvent(new FocusEvent('blur'));
        component.onKeydown(new KeyboardEvent('keydown', { key: 'Enter' }));
        expect(storedAnnotations()).toEqual([]);
        expect(component.open).toBe(true);

        component.onKeydown(new KeyboardEvent('keydown', { key: 'Enter', ctrlKey: true }));
        expect(storedAnnotations()).toHaveLength(1);
        expect(component.open).toBe(false);
    });

    it('Cmd+Enter saves too', () => {
        const component = openOnChat();
        typeBody('<p>une note</p>');

        component.onKeydown(new KeyboardEvent('keydown', { key: 'Enter', metaKey: true }));

        expect(storedAnnotations()).toHaveLength(1);
    });

    it('cancel stores nothing', () => {
        const component = openOnChat();
        typeBody('<p>une note</p>');

        component.cancel();

        expect(component.open).toBe(false);
        expect(storedAnnotations()).toEqual([]);
    });

    it('opening the form resets the editor', () => {
        chapter('<div class="ce-block ce-block--text"><p id="a">le chat dort sur le tapis</p></div>');
        typeBody('<p>reste</p>');
        const onInput = vi.fn();
        editorTextarea().addEventListener('input', onInput);
        select(textOf('#a'), 3, textOf('#a'), 12);

        makeComponent().openForm();

        expect(editorTextarea().value).toBe('');
        expect(onInput).toHaveBeenCalled();
    });
});

describe('annotationForm.openEdit', () => {
    it('openEdit pre-fills the body and save updates the same tempId', () => {
        chapter('<div class="ce-block ce-block--text"><p id="a">le chat dort</p></div>');
        const draft = drafts.addAnnotation(USER_ID, 'chapter', CHAPTER_ID, {
            body: '<p>avant</p>', highlighted: 'chat', prefix: 'le ', suffix: ' dort',
        });

        const component = makeComponent();
        component.openEdit({ tempId: draft.tempId });

        expect(component.open).toBe(true);
        expect(component.centred).toBe(true);
        expect(component.highlighted).toBe('chat');
        expect(editorTextarea().value).toBe('<p>avant</p>');

        typeBody('<p>après</p>');
        component.save();

        const stored = storedAnnotations();
        expect(stored).toHaveLength(1);
        expect(stored[0]).toMatchObject({ tempId: draft.tempId, body: '<p>après</p>', highlighted: 'chat' });
        expect(component.open).toBe(false);
    });

    it('ignores an unknown tempId', () => {
        chapter('<div class="ce-block ce-block--text"><p id="a">le chat dort</p></div>');

        const component = makeComponent();
        component.openEdit({ tempId: 'nope' });

        expect(component.open).toBe(false);
    });
});
