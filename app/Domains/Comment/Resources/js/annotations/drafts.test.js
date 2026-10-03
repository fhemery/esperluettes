import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationDrafts, MODAL_NAME } from './drafts.js';
import * as drafts from '../comment-draft/index.js';

const USER_ID = 7;
const CHAPTER_ID = 42;
const LABEL_ONE = '1 annotation à sauvegarder';
const LABEL_MANY = '__COUNT__ annotations à sauvegarder';

let form;
let component;

function mount() {
    form = document.createElement('form');
    const root = document.createElement('div');
    root.dataset.userId = String(USER_ID);
    root.dataset.entityType = 'chapter';
    root.dataset.entityId = String(CHAPTER_ID);
    root.dataset.labelOne = LABEL_ONE;
    root.dataset.labelMany = LABEL_MANY;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'annotations';
    root.appendChild(input);
    form.appendChild(root);
    document.body.appendChild(form);

    component = annotationDrafts();
    // Alpine: $root is the x-data element.
    component.$root = root;
    component.init();
    return component;
}

function add(highlighted, body = '<p>note</p>') {
    return drafts.addAnnotation(USER_ID, 'chapter', CHAPTER_ID, {
        body,
        highlighted,
        prefix: 'avant ',
        suffix: ' après',
    });
}

function hiddenInput() {
    return form.querySelector('input[name="annotations"]');
}

function submit() {
    form.dispatchEvent(new Event('submit', { cancelable: true }));
}

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
    window.commentDrafts = drafts;
});

afterEach(() => {
    component?.destroy();
    component = null;
    vi.useRealTimers();
});

describe('annotationDrafts', () => {
    it('the banner is hidden with no draft and shows the count with drafts', () => {
        mount();
        expect(component.visible).toBe(false);
        component.destroy();

        add('premier');
        add('second');
        add('troisième');
        mount();

        expect(component.visible).toBe(true);
        expect(component.count).toBe(3);
        expect(component.label).toBe('3 annotations à sauvegarder');
    });

    it('reads the slot once the draft module has loaded when it loads after Alpine', () => {
        add('premier');
        add('second');
        delete window.commentDrafts;
        mount();
        expect(component.count).toBe(0);

        // The draft module is a body-end script; DOMContentLoaded fires after it ran.
        window.commentDrafts = drafts;
        document.dispatchEvent(new Event('DOMContentLoaded'));

        expect(component.count).toBe(2);
    });

    it('uses the singular label for one draft', () => {
        add('premier');
        mount();

        expect(component.label).toBe(LABEL_ONE);
    });

    it('the count follows add / remove events', () => {
        mount();

        const first = add('premier');
        add('second');
        expect(component.count).toBe(2);

        drafts.removeAnnotation(USER_ID, 'chapter', CHAPTER_ID, first.tempId);
        expect(component.count).toBe(1);

        drafts.clearAnnotations(USER_ID, 'chapter', CHAPTER_ID);
        expect(component.count).toBe(0);
        expect(component.visible).toBe(false);
    });

    it('ignores events for another entity', () => {
        mount();

        drafts.addAnnotation(USER_ID, 'chapter', 99, { body: '<p>x</p>', highlighted: 'ailleurs' });

        expect(component.count).toBe(0);
    });

    it('submit fills the hidden input with snake_case items', () => {
        add('premier', '<p>Bien <strong>vu</strong></p>');
        add('second');
        mount();

        submit();

        expect(JSON.parse(hiddenInput().value)).toEqual([
            { body: '<p>Bien <strong>vu</strong></p>', highlighted_text: 'premier', prefix: 'avant ', suffix: ' après' },
            { body: '<p>note</p>', highlighted_text: 'second', prefix: 'avant ', suffix: ' après' },
        ]);
    });

    it('submit with no drafts leaves the input empty', () => {
        mount();

        submit();

        expect(hiddenInput().value).toBe('');
    });

    it('submit reads the slot as it is at submit time', () => {
        mount();
        hiddenInput().value = 'stale';
        add('premier');

        submit();

        expect(JSON.parse(hiddenInput().value)).toHaveLength(1);
    });

    it('Supprimer removes the draft and updates the count', () => {
        const first = add('premier');
        add('second');
        mount();

        component.remove(first.tempId);

        expect(component.count).toBe(1);
        expect(drafts.listAnnotations(USER_ID, 'chapter', CHAPTER_ID).map((d) => d.highlighted)).toEqual(['second']);
    });

    it('Voir les annotations opens the drafts modal', () => {
        mount();
        const opened = vi.fn();
        window.addEventListener('open-modal', opened);

        component.openModal();

        expect(opened.mock.calls[0][0].detail).toBe(MODAL_NAME);
        window.removeEventListener('open-modal', opened);
    });

    it('Modifier opens the capture form in edit mode for that tempId', () => {
        vi.useFakeTimers();
        const draft = add('premier');
        mount();
        const closed = vi.fn();
        const edit = vi.fn();
        window.addEventListener('close-modal', closed);
        window.addEventListener('annotation:open-edit', edit);

        component.edit(draft.tempId);

        expect(closed.mock.calls[0][0].detail).toBe(MODAL_NAME);
        // Deferred past the click: the capture form's click.outside would
        // otherwise close it on the very click that opened it.
        expect(edit).not.toHaveBeenCalled();
        vi.runAllTimers();
        expect(edit.mock.calls[0][0].detail).toEqual({ tempId: draft.tempId });

        window.removeEventListener('close-modal', closed);
        window.removeEventListener('annotation:open-edit', edit);
    });
});
