import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationChangesBanner } from './changes-banner.js';
import * as drafts from '../comment-draft/index.js';

const USER_ID = 7;
const CHAPTER_ID = 42;
const ROOT_ID = 10;
const STALE = 'Cette annotation n\'existe plus.';
const ERROR_ITEMS = 'Certaines modifications ont été refusées :';
const ERROR_GENERIC = 'Enregistrement impossible.';

let component;
let fetchMock;

function mountAnnotable(rootId = ROOT_ID) {
    const annotable = document.createElement('div');
    annotable.dataset.annotable = '';
    annotable.dataset.annotationMode = rootId ? 'pending' : 'draft';
    if (rootId) annotable.dataset.rootCommentId = String(rootId);
    document.body.appendChild(annotable);
}

function mount() {
    const root = document.createElement('div');
    Object.assign(root.dataset, {
        userId: String(USER_ID),
        entityType: 'chapter',
        entityId: String(CHAPTER_ID),
        labelOne: 'Vous avez 1 annotation non sauvegardée',
        labelMany: 'Vous avez __COUNT__ annotations non sauvegardées',
        countOne: '1 annotation',
        countMany: '__COUNT__ annotations',
        discardConfirm: 'Tout annuler ?',
        errorItems: ERROR_ITEMS,
        errorGeneric: ERROR_GENERIC,
        stale: STALE,
        itemEdit: 'Modification',
        itemDelete: 'Suppression',
    });
    document.body.appendChild(root);
    component = annotationChangesBanner();
    component.$root = root;
    component.init();
    return component;
}

function addPending(highlighted = 'un passage') {
    return drafts.addPendingAnnotation(USER_ID, 'chapter', CHAPTER_ID, {
        body: '<p>❤️</p>',
        highlighted,
        prefix: 'avant ',
        suffix: ' après',
    });
}

function jsonResponse(body, status = 200) {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: async () => body,
        text: async () => JSON.stringify(body),
    };
}

function addCountButton(commentId) {
    const button = document.createElement('button');
    button.dataset.annotationsButton = '';
    button.dataset.commentId = String(commentId);
    button.hidden = true;
    const label = document.createElement('span');
    label.dataset.annotationsCount = '';
    button.appendChild(label);
    document.body.appendChild(button);
    return button;
}

const slot = () => drafts.getAnnotationChanges(USER_ID, 'chapter', CHAPTER_ID);

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
    window.commentDrafts = drafts;
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
    component?.destroy();
    component = null;
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('annotationChangesBanner', () => {
    it('shows the plural-aware count and hides at zero', () => {
        mountAnnotable();
        mount();
        expect(component.count).toBe(0);

        addPending('premier');
        expect(component.count).toBe(1);
        expect(component.label).toBe('Vous avez 1 annotation non sauvegardée');

        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 3, '<p>modifiée</p>');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 4);
        expect(component.count).toBe(3);
        expect(component.label).toBe('Vous avez 3 annotations non sauvegardées');

        // Survives a reload: a fresh component reads the stored slot.
        component.destroy();
        mount();
        expect(component.count).toBe(3);
    });

    it('stays hidden without a root comment on the page', () => {
        mountAnnotable(null);
        addPending();
        mount();
        expect(component.count).toBe(0);
    });

    it('sends one PUT and clears the slot on 200', async () => {
        mountAnnotable();
        const button = addCountButton(ROOT_ID);
        const tempId = addPending('premier');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 4);
        mount();
        const list = { comment_id: ROOT_ID, viewer_role: 'commenter', items: [{ id: 5 }, { id: 6 }] };
        fetchMock.mockResolvedValue(jsonResponse(list));
        const refreshed = vi.fn();
        window.addEventListener('annotations:list-refreshed', refreshed);

        await component.save();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe(`/comments/${ROOT_ID}/annotations`);
        expect(options.method).toBe('PUT');
        const body = JSON.parse(options.body);
        expect(body.adds.map((a) => a.key)).toEqual([tempId]);
        expect(body.deletes).toEqual([4]);
        expect(drafts.countAnnotationChanges(USER_ID, 'chapter', CHAPTER_ID)).toBe(0);
        expect(component.count).toBe(0);
        expect(refreshed.mock.calls[0][0].detail).toEqual({ commentId: ROOT_ID, list });
        expect(button.hidden).toBe(false);
        expect(button.querySelector('[data-annotations-count]').textContent).toBe('2 annotations');
        window.removeEventListener('annotations:list-refreshed', refreshed);
    });

    it('keeps the slot and dispatches keyed errors on 422', async () => {
        mountAnnotable();
        const tempId = addPending('le passage refusé');
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 3, '<p>modifiée</p>');
        mount();
        fetchMock.mockResolvedValue(jsonResponse({
            errors: { [`adds.${tempId}`]: ['Trop long'], 'edits.3': [STALE] },
        }, 422));
        const onErrors = vi.fn();
        window.addEventListener('annotations:save-errors', onErrors);

        await component.save();

        expect(slot().adds).toHaveLength(1);
        expect(slot().edits).toEqual({ 3: '<p>modifiée</p>' });
        expect(component.count).toBe(2);
        expect(component.itemErrors).toEqual({ [`adds.${tempId}`]: 'Trop long', 'edits.3': STALE });
        expect(onErrors.mock.calls[0][0].detail).toEqual({
            commentId: ROOT_ID,
            errors: { [`adds.${tempId}`]: 'Trop long', 'edits.3': STALE },
        });
        expect(component.error).toBe(ERROR_ITEMS);
        expect(component.errorLines).toEqual(['« le passage refusé » : Trop long', `Modification : ${STALE}`]);
        window.removeEventListener('annotations:save-errors', onErrors);
    });

    it('marks every pending item stale on 404', async () => {
        mountAnnotable();
        const tempId = addPending();
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 4);
        mount();
        fetchMock.mockResolvedValue(jsonResponse({ message: 'Not found' }, 404));
        const onErrors = vi.fn();
        window.addEventListener('annotations:save-errors', onErrors);

        await component.save();

        const expected = { [`adds.${tempId}`]: STALE, 'deletes.4': STALE };
        expect(component.itemErrors).toEqual(expected);
        expect(onErrors.mock.calls[0][0].detail).toEqual({ commentId: ROOT_ID, errors: expected });
        expect(component.count).toBe(2);
        window.removeEventListener('annotations:save-errors', onErrors);
    });

    it('shows a generic message on a network error and keeps the slot', async () => {
        mountAnnotable();
        addPending();
        mount();
        fetchMock.mockRejectedValue(new TypeError('Failed to fetch'));

        await component.save();

        expect(component.error).toBe(ERROR_GENERIC);
        expect(component.saving).toBe(false);
        expect(component.count).toBe(1);
    });

    it('discards all after confirmation', () => {
        mountAnnotable();
        addPending();
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 4);
        mount();
        const confirm = vi.fn().mockReturnValueOnce(false).mockReturnValueOnce(true);
        vi.stubGlobal('confirm', confirm);

        component.discardAll();
        expect(component.count).toBe(2);

        component.discardAll();
        expect(confirm).toHaveBeenCalledWith('Tout annuler ?');
        expect(component.count).toBe(0);
        expect(drafts.countAnnotationChanges(USER_ID, 'chapter', CHAPTER_ID)).toBe(0);
    });

    it('« Voir » opens the pop-up on the root comment', () => {
        mountAnnotable();
        mount();
        const onOpen = vi.fn();
        window.addEventListener('annotations:open', onOpen);

        component.show();

        expect(onOpen.mock.calls[0][0].detail).toEqual({ commentId: ROOT_ID });
        window.removeEventListener('annotations:open', onOpen);
    });
});
