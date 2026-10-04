import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationsModal, MODAL_NAME, REPLY_EDITOR_ID, STATE_ADDED, STATE_DELETED, STATE_EDITED } from './modal.js';
import * as drafts from '../comment-draft/index.js';

const LABEL_ONE = '1 annotation';
const LABEL_MANY = '__COUNT__ annotations';
const LOAD_ERROR = 'Impossible de charger';
const ACTION_ERROR = 'Action impossible';
const DELETE_CONFIRM = 'Les réponses seront aussi supprimées.';
const DELETE_REPLY_CONFIRM = 'Supprimer cette réponse ?';
const REPLY_EMPTY = 'Réponse vide.';
const REPLY_TOO_LONG = 'Réponse trop longue.';
const STALE = 'Cette annotation n’existe plus.';
const CSRF = 'csrf-token-value';
const USER_ID = 7;
const CHAPTER_ID = 42;

let component;
let fetchMock;

function item(id, overrides = {}) {
    return {
        id,
        comment_id: 10,
        body: `<p>Avis ${id}</p>`,
        highlighted_text: `passage ${id}`,
        is_processed: null,
        can_mark_as_processed: false,
        can_delete: false,
        ...overrides,
    };
}

function listResponse(viewerRole, items, commentId = 10) {
    return { comment_id: commentId, viewer_role: viewerRole, items };
}

function jsonResponse(body, status = 200) {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: async () => body,
        text: async () => JSON.stringify(body),
    };
}

function addButton(commentId, count) {
    const button = document.createElement('button');
    button.dataset.annotationsButton = '';
    button.dataset.commentId = String(commentId);
    const label = document.createElement('span');
    label.dataset.annotationsCount = '';
    label.textContent = `${count} annotations`;
    button.appendChild(label);
    document.body.appendChild(button);
    return button;
}

function mount() {
    const root = document.createElement('div');
    root.dataset.labelOne = LABEL_ONE;
    root.dataset.labelMany = LABEL_MANY;
    root.dataset.loadError = LOAD_ERROR;
    root.dataset.actionError = ACTION_ERROR;
    root.dataset.deleteWithRepliesConfirm = DELETE_CONFIRM;
    root.dataset.deleteReplyConfirm = DELETE_REPLY_CONFIRM;
    root.dataset.replyMaxLength = '20';
    root.dataset.replyEmpty = REPLY_EMPTY;
    root.dataset.replyTooLong = REPLY_TOO_LONG;
    root.dataset.stale = STALE;
    root.dataset.userId = String(USER_ID);
    root.dataset.entityType = 'chapter';
    root.dataset.entityId = String(CHAPTER_ID);
    document.body.appendChild(root);

    component = annotationsModal();
    // Alpine: $root is the x-data element.
    component.$root = root;
    component.init();
    return component;
}

beforeEach(() => {
    document.head.innerHTML = `<meta name="csrf-token" content="${CSRF}">`;
    document.body.innerHTML = '';
    localStorage.clear();
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

const slot = () => drafts.getAnnotationChanges(USER_ID, 'chapter', CHAPTER_ID);

function addPending(highlighted = 'nouveau passage', body = '<p>🔥</p>') {
    return drafts.addPendingAnnotation(USER_ID, 'chapter', CHAPTER_ID, { body, highlighted, prefix: '', suffix: '' });
}

async function openAsCommenter(items) {
    fetchMock.mockResolvedValueOnce(jsonResponse(listResponse('commenter', items)));
    mount();
    await component.open(10);
}

const rowById = (id) => component.rows.find((r) => r.id === id);

describe('annotationsModal — commenter overlay', () => {
    const own = (id, overrides = {}) => item(id, { can_edit: true, replies: [], ...overrides });

    it('overlays pending edits and deletes on the commenter’s rows and lists pending adds', async () => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>Avis revu</p>');
        await openAsCommenter([own(1), own(2), own(3)]);
        const tempId = addPending('ajout');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 2);

        expect(component.rows.map((r) => r.state)).toEqual([STATE_EDITED, STATE_DELETED, null, STATE_ADDED]);
        expect(rowById(1).body).toBe('<p>Avis revu</p>');
        expect(rowById(2).body).toBe('<p>Avis 2</p>');
        const added = component.rows[3];
        expect(added.tempId).toBe(tempId);
        expect(added.highlighted_text).toBe('ajout');
        expect(added.body).toBe('<p>🔥</p>');

        // Edit offered on untouched and edited rows, never on a row pending deletion or a pending add.
        expect(component.canEdit(rowById(1))).toBe(true);
        expect(component.canEdit(rowById(2))).toBe(false);
        expect(component.canEdit(rowById(3))).toBe(true);
        expect(component.canEdit(added)).toBe(false);
        expect(component.canRemove(rowById(2))).toBe(false);
        expect(component.canRemove(added)).toBe(false);
        expect(component.canUndo(rowById(3))).toBe(false);
        // The server list itself is untouched.
        expect(component.items[0].body).toBe('<p>Avis 1</p>');

        // « Modifier » hands the pending body to the capture form.
        const edited = vi.fn();
        window.addEventListener('annotations:edit-saved-row', edited);
        component.edit(rowById(1));
        component.edit(rowById(2));
        window.removeEventListener('annotations:edit-saved-row', edited);
        expect(edited).toHaveBeenCalledTimes(1);
        expect(edited.mock.calls[0][0].detail).toEqual({ id: 1, body: '<p>Avis revu</p>', highlighted: 'passage 1' });
    });

    it('lists pending adds when the server list is empty', async () => {
        addPending('seul ajout');
        await openAsCommenter([]);

        expect(component.items).toEqual([]);
        expect(component.rows).toHaveLength(1);
        expect(component.rows[0].state).toBe(STATE_ADDED);
    });

    it('undoes a pending edit, a pending delete and a pending add from the row', async () => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>revu</p>');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 2);
        addPending();
        await openAsCommenter([own(1), own(2)]);

        component.undo(rowById(1));
        component.undo(rowById(2));
        component.undo(component.rows.find((r) => r.state === STATE_ADDED));

        expect(slot()).toEqual({ adds: [], edits: {}, deletes: [] });
        expect(component.rows.map((r) => r.state)).toEqual([null, null]);
        expect(rowById(1).body).toBe('<p>Avis 1</p>');
    });

    it('asks for confirmation before deleting a row that has replies, and not otherwise', async () => {
        const confirm = vi.fn().mockReturnValueOnce(false).mockReturnValueOnce(true);
        vi.stubGlobal('confirm', confirm);
        await openAsCommenter([own(1), own(2, { replies: [item(20)] })]);

        component.remove(rowById(1));
        expect(confirm).not.toHaveBeenCalled();
        expect(slot().deletes).toEqual([1]);

        component.remove(rowById(2));
        expect(confirm).toHaveBeenCalledWith(DELETE_CONFIRM);
        expect(slot().deletes).toEqual([1]);

        component.remove(rowById(2));
        expect(confirm).toHaveBeenCalledTimes(2);
        expect(slot().deletes).toEqual([1, 2]);
        // Pending only: nothing reached the server.
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('shows a save error on the matching row and lets the user remove the item', async () => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>revu</p>');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 2);
        const tempId = addPending();
        await openAsCommenter([own(1), own(2), own(3)]);

        window.dispatchEvent(new CustomEvent('annotations:save-errors', {
            detail: {
                commentId: 10,
                errors: { [`adds.${tempId}`]: 'Trop long.', 'edits.1': 'Introuvable.', 'deletes.2': 'Introuvable (suppr).' },
            },
        }));

        const added = () => component.rows.find((r) => r.state === STATE_ADDED);
        expect(component.rowError(added())).toBe('Trop long.');
        expect(component.rowError(rowById(1))).toBe('Introuvable.');
        expect(component.rowError(rowById(2))).toBe('Introuvable (suppr).');
        expect(component.rowError(rowById(3))).toBe('');

        // « Retirer » undoes the refused item; its error goes with it.
        component.undo(rowById(1));
        expect(slot().edits).toEqual({});
        expect(component.rowError(rowById(1))).toBe('');
        expect(component.rowError(added())).toBe('Trop long.');

        component.undo(added());
        expect(slot().adds).toEqual([]);
        expect(Object.keys(component.saveErrors)).toEqual(['deletes.2']);
    });

    it('lists a pending edit or delete whose annotation is gone from the server list, flagged stale, removable alone', async () => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 9, '<p>trop tard</p>');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 8);
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>revu</p>');
        const tempId = addPending();
        await openAsCommenter([own(1)]);

        expect(component.rows.map((r) => r.state)).toEqual([STATE_EDITED, STATE_EDITED, STATE_DELETED, STATE_ADDED]);
        const goneEdit = component.rows[1];
        const goneDelete = component.rows[2];
        expect(goneEdit.body).toBe('<p>trop tard</p>');
        expect(goneEdit.highlighted_text).toBe('');
        expect(component.rowError(goneEdit)).toBe(STALE);
        expect(component.rowError(goneDelete)).toBe(STALE);
        expect(component.rowError(rowById(1))).toBe('');
        for (const row of [goneEdit, goneDelete]) {
            expect(component.canEdit(row)).toBe(false);
            expect(component.canRemove(row)).toBe(false);
            expect(component.canReply(row)).toBe(false);
        }

        // « Retirer » drops only that change.
        component.undo(goneEdit);
        component.undo(component.rows.find((r) => r.state === STATE_DELETED));
        expect(slot()).toEqual({
            adds: [expect.objectContaining({ tempId })],
            edits: { 1: '<p>revu</p>' },
            deletes: [],
        });
        expect(component.rows.map((r) => r.state)).toEqual([STATE_EDITED, STATE_ADDED]);
    });

    it('flags no stale change while the list is loading or failed to load', async () => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 9, '<p>trop tard</p>');
        fetchMock.mockRejectedValueOnce(new Error('network'));
        mount();
        await component.open(10);

        expect(component.error).toBe(LOAD_ERROR);
        expect(component.rows).toEqual([]);
    });

    it('ignores save errors for another comment', async () => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>revu</p>');
        await openAsCommenter([own(1)]);

        window.dispatchEvent(new CustomEvent('annotations:save-errors', {
            detail: { commentId: 99, errors: { 'edits.1': 'Introuvable.' } },
        }));

        expect(component.rowError(rowById(1))).toBe('');
    });

    it('replaces the cached list on annotations:list-refreshed', async () => {
        await openAsCommenter([own(1), own(2)]);

        window.dispatchEvent(new CustomEvent('annotations:list-refreshed', {
            detail: { commentId: 10, list: listResponse('commenter', [own(2, { body: '<p>enregistré</p>' }), own(5)]) },
        }));
        expect(component.items.map((i) => i.id)).toEqual([2, 5]);
        expect(rowById(2).body).toBe('<p>enregistré</p>');

        // Reopening serves the refreshed cache without a fetch.
        await component.open(10);
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(component.items.map((i) => i.id)).toEqual([2, 5]);

        // A refresh for a comment not on screen still replaces its cache.
        window.dispatchEvent(new CustomEvent('annotations:list-refreshed', {
            detail: { commentId: 20, list: listResponse('commenter', [own(8)], 20) },
        }));
        expect(component.items.map((i) => i.id)).toEqual([2, 5]);
        await component.open(20);
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(component.items.map((i) => i.id)).toEqual([8]);
    });

    it.each(['author', 'moderator'])('shows no overlay to an %s', async (role) => {
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>revu</p>');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 2);
        addPending();
        fetchMock.mockResolvedValueOnce(jsonResponse(listResponse(role, [
            item(1, { can_edit: false, replies: [] }),
            item(2, { can_edit: false, replies: [] }),
        ])));
        mount();
        await component.open(10);

        expect(component.rows.map((r) => r.id)).toEqual([1, 2]);
        expect(rowById(1).body).toBe('<p>Avis 1</p>');
        for (const row of component.rows) {
            expect(row.state).toBeUndefined();
            expect(component.canEdit(row)).toBe(false);
            expect(component.canUndo(row)).toBe(false);
        }
        window.dispatchEvent(new CustomEvent('annotations:save-errors', {
            detail: { commentId: 10, errors: { 'edits.1': 'Introuvable.' } },
        }));
        expect(component.rowError(rowById(1))).toBe('');
    });
});

describe('annotationsModal — replies', () => {
    function reply(id, overrides = {}) {
        return {
            id,
            comment_id: 10,
            parent_annotation_id: 1,
            author_profile: { user_id: 3, display_name: `Plume ${id}`, slug: `plume-${id}`, avatar_url: '' },
            body: `<p>Réponse ${id}</p>`,
            created_at: '2026-10-01T10:00:00.000000Z',
            replies: [],
            can_delete: false,
            ...overrides,
        };
    }

    function editorBody(html) {
        let textarea = document.getElementById(`quill-editor-area-${REPLY_EDITOR_ID}`);
        if (!textarea) {
            textarea = document.createElement('textarea');
            textarea.id = `quill-editor-area-${REPLY_EDITOR_ID}`;
            document.body.appendChild(textarea);
        }
        textarea.value = html;
    }

    async function openAs(role, items) {
        fetchMock.mockResolvedValueOnce(jsonResponse(listResponse(role, items)));
        mount();
        await component.open(10);
    }

    it('renders each root’s replies in date order with writer and date', async () => {
        document.documentElement.lang = 'fr';
        await openAs('author', [item(1, {
            replies: [
                reply(20, { created_at: '2026-09-30T08:00:00.000000Z' }),
                reply(21, { created_at: '2026-10-01T09:00:00.000000Z' }),
            ],
        })]);

        const replies = component.rows[0].replies;
        expect(replies.map((r) => r.id)).toEqual([20, 21]);
        expect(component.replyAuthor(replies[0])).toBe('Plume 20');
        expect(component.replyDate(replies[0])).toBe('30/09/2026');
        expect(component.replyDate(replies[1])).toBe('01/10/2026');
    });

    it('shows « Répondre » only when can_reply is true', async () => {
        await openAs('author', [item(1, { can_reply: true, replies: [] }), item(2, { can_reply: false, replies: [] })]);

        expect(component.canReply(component.rows[0])).toBe(true);
        expect(component.canReply(component.rows[1])).toBe(false);

        component.startReply(component.rows[1]);
        expect(component.replyingTo).toBeNull();
        component.startReply(component.rows[0]);
        expect(component.replyingTo).toBe(1);
    });

    it('opens one reply editor at a time and closes it on cancel', async () => {
        await openAs('author', [item(1, { can_reply: true, replies: [] }), item(2, { can_reply: true, replies: [] })]);

        component.startReply(component.rows[0]);
        component.startReply(component.rows[1]);
        expect(component.replyingTo).toBe(2);

        component.cancelReply();
        expect(component.replyingTo).toBeNull();
    });

    it('moves the reply editor under the row even when called from a nested x-data button', async () => {
        fetchMock.mockResolvedValueOnce(jsonResponse(listResponse('author', [item(1, { can_reply: true, replies: [] })])));
        const root = mount().$root;
        root.innerHTML = '<div data-reply-slot="1"></div><div hidden><div data-reply-editor></div></div>';
        component.init();
        await component.open(10);

        // <x-shared::button> has its own x-data: there, $root is the button.
        component.$root = document.createElement('button');
        component.startReply(component.rows[0]);

        const editor = root.querySelector('[data-reply-editor]');
        expect(editor.parentElement).toBe(root.querySelector('[data-reply-slot="1"]'));
        component.cancelReply();
        expect(editor.parentElement.hidden).toBe(true);
    });

    it('tracks the reply editor’s validity from its editor-valid events only', async () => {
        await openAs('author', [item(1, { can_reply: true, replies: [] })]);
        component.startReply(component.rows[0]);
        expect(component.replyValid).toBe(false);

        window.dispatchEvent(new CustomEvent('editor-valid', { detail: { id: 'another-editor', valid: true } }));
        expect(component.replyValid).toBe(false);
        window.dispatchEvent(new CustomEvent('editor-valid', { detail: { id: REPLY_EDITOR_ID, valid: true } }));
        expect(component.replyValid).toBe(true);
    });

    it.each([
        ['author', true],
        ['commenter', false],
    ])('posts a reply, appends it and shows the author hint to an author only (%s)', async (role, hint) => {
        const created = reply(30, { body: '<p>Merci</p>', can_delete: true });
        await openAs(role, [item(1, { can_reply: true, replies: [reply(20)] })]);
        fetchMock.mockResolvedValueOnce(jsonResponse(created, 201));

        component.startReply(component.rows[0]);
        editorBody('<p>Merci</p>');
        await component.sendReply();

        const [url, init] = fetchMock.mock.calls[1];
        expect(url).toBe('/comments/annotations/1/replies');
        expect(init.method).toBe('POST');
        expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);
        expect(JSON.parse(init.body)).toEqual({ body: '<p>Merci</p>' });

        expect(component.rows[0].replies.map((r) => r.id)).toEqual([20, 30]);
        expect(component.replyingTo).toBeNull();
        expect(component.showsHint(component.rows[0])).toBe(hint);

        // The cached copy follows: reopening shows the reply without a fetch.
        await component.open(10);
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(component.rows[0].replies.map((r) => r.id)).toEqual([20, 30]);
    });

    it.each([
        ['<p><br></p>', REPLY_EMPTY],
        [`<p>${'a'.repeat(21)}</p>`, REPLY_TOO_LONG],
    ])('refuses an invalid reply body without calling the server (%s)', async (body, message) => {
        await openAs('author', [item(1, { can_reply: true, replies: [] })]);

        component.startReply(component.rows[0]);
        editorBody(body);
        await component.sendReply();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(component.replyError).toBe(message);
        expect(component.replyingTo).toBe(1);
    });

    it('keeps the editor open with an error when the POST fails', async () => {
        await openAs('author', [item(1, { can_reply: true, replies: [] })]);
        fetchMock.mockResolvedValueOnce(jsonResponse({ message: 'nope', errors: { body: ['Trop long'] } }, 422));

        component.startReply(component.rows[0]);
        editorBody('<p>Merci</p>');
        await component.sendReply();

        expect(component.replyingTo).toBe(1);
        expect(component.replyError).toBe(ACTION_ERROR);
        expect(component.rows[0].replies).toEqual([]);
        expect(component.showsHint(component.rows[0])).toBe(false);
    });

    it('deletes the viewer’s own reply after confirmation via the reply route', async () => {
        const confirm = vi.fn().mockReturnValueOnce(false).mockReturnValueOnce(true);
        vi.stubGlobal('confirm', confirm);
        await openAs('author', [item(1, { replies: [reply(20, { can_delete: true }), reply(21)] })]);
        fetchMock.mockResolvedValueOnce({ ok: true, status: 204, text: async () => '' });

        expect(component.canDeleteReply(component.rows[0].replies[0])).toBe(true);
        expect(component.canDeleteReply(component.rows[0].replies[1])).toBe(false);

        await component.removeReply(component.rows[0].replies[0]);
        expect(confirm).toHaveBeenCalledWith(DELETE_REPLY_CONFIRM);
        expect(fetchMock).toHaveBeenCalledTimes(1);

        await component.removeReply(component.rows[0].replies[0]);
        const [url, init] = fetchMock.mock.calls[1];
        expect(url).toBe('/comments/annotations/replies/20');
        expect(init.method).toBe('DELETE');
        expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);
        expect(component.rows[0].replies.map((r) => r.id)).toEqual([21]);

        // A reply the viewer may not delete is never sent.
        await component.removeReply(component.rows[0].replies[0]);
        expect(fetchMock).toHaveBeenCalledTimes(2);
    });

    it('lets a moderator delete a reply through the moderator route', async () => {
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
        const button = addButton(10, 1);
        await openAs('moderator', [item(1, { can_delete: true, can_reply: false, replies: [reply(20, { can_delete: true })] })]);
        fetchMock.mockResolvedValueOnce({ ok: true, status: 204, text: async () => '' });

        expect(component.canReply(component.rows[0])).toBe(false);
        await component.removeReply(component.rows[0].replies[0]);

        const [url, init] = fetchMock.mock.calls[1];
        expect(url).toBe('/comments/annotations/20');
        expect(init.method).toBe('DELETE');
        expect(component.rows.map((r) => r.id)).toEqual([1]);
        expect(component.rows[0].replies).toEqual([]);
        // Replies are not counted on the « N annotations » button.
        expect(button.querySelector('[data-annotations-count]').textContent).toBe('1 annotations');
    });

    it('shows the action error when a reply delete fails', async () => {
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
        await openAs('author', [item(1, { replies: [reply(20, { can_delete: true })] })]);
        fetchMock.mockResolvedValueOnce(jsonResponse({ message: 'nope' }, 403));

        await component.removeReply(component.rows[0].replies[0]);

        expect(component.error).toBe(ACTION_ERROR);
        expect(component.rows[0].replies.map((r) => r.id)).toEqual([20]);
    });
});

describe('annotationsModal', () => {
    it('opens the shared modal by name', async () => {
        fetchMock.mockResolvedValue(jsonResponse(listResponse('commenter', [item(1)])));
        const opened = vi.fn();
        window.addEventListener('open-modal', opened);
        mount();

        await component.open(10);

        expect(opened).toHaveBeenCalledTimes(1);
        expect(opened.mock.calls[0][0].detail).toBe(MODAL_NAME);
        window.removeEventListener('open-modal', opened);
    });

    it('fetches once per comment and reuses the cache on reopen', async () => {
        fetchMock.mockImplementation(async (url) => {
            const id = Number(String(url).match(/comments\/(\d+)\/annotations/)[1]);
            return jsonResponse(listResponse('author', [item(id * 100)], id));
        });
        mount();

        await component.open(10);
        await component.open(20);
        await component.open(10);

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(fetchMock.mock.calls[0][0]).toBe('/comments/10/annotations');
        expect(fetchMock.mock.calls[1][0]).toBe('/comments/20/annotations');
        expect(component.items.map((i) => i.id)).toEqual([1000]);
    });

    it('commenter view: rows without actions and without processed marker', async () => {
        fetchMock.mockResolvedValue(jsonResponse(listResponse('commenter', [item(1), item(2)])));
        mount();

        await component.open(10);

        expect(component.items).toHaveLength(2);
        for (const row of component.items) {
            expect(component.canToggle(row)).toBe(false);
            expect(component.canRemove(row)).toBe(false);
            expect(component.showsProcessed(row)).toBe(false);
        }
    });

    it('author view: toggle calls PUT and flips the row label', async () => {
        const row = item(5, { is_processed: false, can_mark_as_processed: true });
        fetchMock
            .mockResolvedValueOnce(jsonResponse(listResponse('author', [row])))
            .mockResolvedValueOnce(jsonResponse({ id: 5, is_processed: true }));
        mount();
        await component.open(10);
        const shown = component.items[0];

        expect(component.canToggle(shown)).toBe(true);
        expect(component.showsProcessed(shown)).toBe(false);

        await component.toggle(shown);

        const [url, init] = fetchMock.mock.calls[1];
        expect(url).toBe('/comments/annotations/5/processed');
        expect(init.method).toBe('PUT');
        expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);
        expect(init.headers['Content-Type']).toBe('application/json');
        expect(JSON.parse(init.body)).toEqual({ value: true });
        expect(component.items[0].is_processed).toBe(true);
        expect(component.showsProcessed(component.items[0])).toBe(true);

        // The cached copy follows: reopening shows the new state without a fetch.
        await component.open(10);
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(component.items[0].is_processed).toBe(true);
    });

    it('author view: a failed toggle keeps the row and shows the error line', async () => {
        const row = item(5, { is_processed: false, can_mark_as_processed: true });
        fetchMock
            .mockResolvedValueOnce(jsonResponse(listResponse('author', [row])))
            .mockResolvedValueOnce(jsonResponse({ message: 'nope' }, 422));
        mount();
        await component.open(10);

        await component.toggle(component.items[0]);

        expect(component.items[0].is_processed).toBe(false);
        expect(component.error).toBe(ACTION_ERROR);
    });

    it('moderator view: delete calls DELETE, removes the row and decrements the count', async () => {
        const button = addButton(10, 2);
        fetchMock
            .mockResolvedValueOnce(jsonResponse(listResponse('moderator', [
                item(1, { can_delete: true }),
                item(2, { can_delete: true }),
            ])))
            .mockResolvedValueOnce({ ok: true, status: 204, text: async () => '' });
        mount();
        await component.open(10);

        expect(component.canRemove(component.items[0])).toBe(true);
        await component.remove(component.items[0]);

        const [url, init] = fetchMock.mock.calls[1];
        expect(url).toBe('/comments/annotations/1');
        expect(init.method).toBe('DELETE');
        expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);
        expect(component.items.map((i) => i.id)).toEqual([2]);
        expect(button.querySelector('[data-annotations-count]').textContent).toBe(LABEL_ONE);
        expect(button.hidden).toBe(false);

        // Reopening uses the updated cache.
        await component.open(10);
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(component.items.map((i) => i.id)).toEqual([2]);
    });

    it('moderator view: deleting the last annotation hides the button', async () => {
        const button = addButton(10, 1);
        fetchMock
            .mockResolvedValueOnce(jsonResponse(listResponse('moderator', [item(1, { can_delete: true })])))
            .mockResolvedValueOnce({ ok: true, status: 204, text: async () => '' });
        mount();
        await component.open(10);

        await component.remove(component.items[0]);

        expect(component.items).toEqual([]);
        expect(button.hidden).toBe(true);
    });

    it('a failed load shows the error line and is not cached', async () => {
        fetchMock
            .mockResolvedValueOnce(jsonResponse({ message: 'Forbidden' }, 403))
            .mockResolvedValueOnce(jsonResponse(listResponse('commenter', [item(1)])));
        mount();

        await component.open(10);
        expect(component.error).toBe(LOAD_ERROR);
        expect(component.items).toEqual([]);

        await component.open(10);
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(component.error).toBe('');
        expect(component.items).toHaveLength(1);
    });

    it('renders highlighted text as text, never as HTML', async () => {
        const hostile = '<img src=x onerror="window.__pwned = true">';
        fetchMock.mockResolvedValue(jsonResponse(listResponse('commenter', [item(1, { highlighted_text: hostile })])));
        mount();

        await component.open(10);

        // The component hands the raw string to the template untouched; the
        // template binds it with x-text (asserted by the Blade view test).
        expect(component.items[0].highlighted_text).toBe(hostile);
        expect(document.body.innerHTML).not.toContain('<img');
        expect(window.__pwned).toBeUndefined();
    });
});
