import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationPopover, POPOVER_ID, REPLY_EDITOR_ID } from './popover.js';
import { createOverlayStore } from './store.js';

const ROOT_A = 10;
const ROOT_B = 11;
const ACTION_ERROR = 'Action impossible';
const REPLY_EMPTY = 'Réponse vide.';
const REPLY_TOO_LONG = 'Réponse trop longue.';

let component;
let overlay;
let popoverStore;
let watchers;
let fetchMock;
let avatar;

function item(id, overrides = {}) {
    return {
        id,
        author_id: 3,
        author_profile: { display_name: `Lecteur ${id}`, avatar_url: `/a/${id}.png` },
        body: `<p>Avis ${id}</p>`,
        highlighted_text: `passage ${id}`,
        prefix: '',
        suffix: '',
        is_processed: false,
        is_emptied: false,
        can_reply: true,
        can_mark_as_processed: true,
        replies: [],
        ...overrides,
    };
}

function jsonResponse(body, status = 200) {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: async () => body,
        text: async () => JSON.stringify(body),
    };
}

async function loadOverlay(lists, role = 'author') {
    await overlay.load({ role, entityType: 'chapter', entityId: 42, userId: 1, url: '' });
    overlay.serverLists = lists;
    overlay.compose();
}

function mount() {
    const root = document.createElement('div');
    root.dataset.position = '{index} / {total}';
    root.dataset.actionError = ACTION_ERROR;
    root.dataset.replyEmpty = REPLY_EMPTY;
    root.dataset.replyTooLong = REPLY_TOO_LONG;
    root.dataset.replyMaxLength = '20';
    root.dataset.defaultAvatar = '/default.svg';
    document.body.appendChild(root);

    const panel = document.createElement('div');
    panel.dataset.annotationPopoverPanel = '';
    panel.tabIndex = -1;
    panel.innerHTML = `<div class="ql-editor"></div><textarea id="quill-editor-area-${REPLY_EDITOR_ID}"></textarea>`;
    document.body.appendChild(panel);

    component = annotationPopover();
    component.$root = root;
    component.$store = { popover: popoverStore, annotationOverlay: overlay };
    component.$watch = (expression, callback) => { watchers[expression] = callback; };
    component.$nextTick = (fn) => fn();
    component.init();
    return component;
}

function openGroup(keys) {
    avatar.dispatchEvent(new CustomEvent('annotation-overlay:open', {
        bubbles: true,
        detail: { groupKey: keys[0], keys },
    }));
}

function replyBody(value) {
    document.getElementById(`quill-editor-area-${REPLY_EDITOR_ID}`).value = value;
}

beforeEach(async () => {
    document.body.innerHTML = '';
    fetchMock = vi.fn();
    globalThis.fetch = fetchMock;
    watchers = {};
    popoverStore = { openId: null };
    overlay = createOverlayStore();
    await loadOverlay([
        { comment_id: ROOT_A, items: [item(1), item(2)] },
        { comment_id: ROOT_B, items: [item(3, { author_id: 4 })] },
    ]);

    avatar = document.createElement('button');
    avatar.dataset.annotationGroup = 'id:1';
    document.body.appendChild(avatar);
    mount();
});

afterEach(() => {
    component.destroy();
    overlay.destroy();
    vi.restoreAllMocks();
});

describe('annotation popover — shell', () => {
    it('opens on the overlay event with the group visible members', () => {
        openGroup(['id:1', 'id:2', 'id:3']);

        expect(component.open).toBe(true);
        expect(component.members.map((r) => r.id)).toEqual([1, 2, 3]);
        expect(component.current.id).toBe(1);
        expect(component.position).toBe('1 / 3');
        expect(popoverStore.openId).toBe(POPOVER_ID);
    });

    it('leaves out the group rows that are not visible', () => {
        overlay.filters.hiddenCommenterIds = new Set([4]);

        openGroup(['id:1', 'id:3']);

        expect(component.members.map((r) => r.id)).toEqual([1]);
        expect(component.position).toBe('1 / 1');
    });

    it('cycles through the members with the arrows', () => {
        openGroup(['id:1', 'id:2', 'id:3']);

        component.next();
        expect(component.position).toBe('2 / 3');
        component.next();
        component.next();
        expect(component.current.id).toBe(1);
        component.previous();
        expect(component.current.id).toBe(3);
        expect(component.position).toBe('3 / 3');
    });

    it('closes on Escape and gives the focus back to the avatar', () => {
        openGroup(['id:1']);

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        expect(component.open).toBe(false);
        expect(document.activeElement).toBe(avatar);
        expect(popoverStore.openId).toBe(null);
    });

    it('closes on a click outside, not on a click inside the panel', () => {
        openGroup(['id:1']);
        const panel = document.querySelector('[data-annotation-popover-panel]');

        panel.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(component.open).toBe(true);

        document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(component.open).toBe(false);
        expect(document.activeElement).toBe(avatar);
    });

    it('closes when another popover claims the open id', () => {
        openGroup(['id:1']);

        popoverStore.openId = 'other';
        watchers['$store.popover.openId']('other');

        expect(component.open).toBe(false);
    });

    it('closes when the heat map turns on', () => {
        openGroup(['id:1']);

        overlay.heatOn = true;
        component.sync();

        expect(component.open).toBe(false);
    });

    it('shows the server body, the placeholder of an emptied row included', async () => {
        await loadOverlay([{ comment_id: ROOT_A, items: [item(1, { body: '<p>&lt;Annotation supprimée&gt;</p>', is_emptied: true })] }]);

        openGroup(['id:1']);

        expect(component.current.body).toBe('<p>&lt;Annotation supprimée&gt;</p>');
        expect(component.authorName(component.current)).toBe('Lecteur 1');
        expect(component.authorAvatar(component.current)).toBe('/a/1.png');
    });
});

describe('annotation popover — processed toggle', () => {
    it('is offered only on rows the viewer may mark as processed', async () => {
        await loadOverlay([{ comment_id: ROOT_A, items: [item(1, { can_mark_as_processed: false, is_processed: null })] }], 'commenter');

        openGroup(['id:1']);

        expect(component.canToggle(component.current)).toBe(false);
    });

    it('calls setProcessed, updates the row and dispatches list-refreshed', async () => {
        overlay.filters.showProcessed = true;
        fetchMock.mockResolvedValue(jsonResponse({ id: 1, is_processed: true }));
        const refreshed = vi.fn();
        window.addEventListener('annotations:list-refreshed', refreshed);
        openGroup(['id:1', 'id:2']);

        await component.toggleProcessed();

        expect(fetchMock.mock.calls[0][0]).toBe('/comments/annotations/1/processed');
        expect(JSON.parse(fetchMock.mock.calls[0][1].body)).toEqual({ value: true });
        expect(overlay.rows.find((r) => r.id === 1).is_processed).toBe(true);
        expect(component.current.id).toBe(1);
        expect(refreshed).toHaveBeenCalledTimes(1);
        const detail = refreshed.mock.calls[0][0].detail;
        expect(detail.commentId).toBe(ROOT_A);
        expect(detail.list.viewer_role).toBe('author');
        expect(detail.list.items.map((r) => [r.id, r.is_processed])).toEqual([[1, true], [2, false]]);
        window.removeEventListener('annotations:list-refreshed', refreshed);
    });

    it('moves to the next member when the processed row leaves the group', async () => {
        fetchMock.mockResolvedValue(jsonResponse({ id: 1, is_processed: true }));
        openGroup(['id:1', 'id:2']);

        await component.toggleProcessed();

        expect(component.open).toBe(true);
        expect(component.current.id).toBe(2);
        expect(component.position).toBe('1 / 1');
    });

    it('closes when the processed row was the last of the group', async () => {
        fetchMock.mockResolvedValue(jsonResponse({ id: 3, is_processed: true }));
        openGroup(['id:3']);

        await component.toggleProcessed();

        expect(component.open).toBe(false);
    });

    it('shows an error and leaves the row as it was on failure', async () => {
        fetchMock.mockResolvedValue(jsonResponse({}, 500));
        openGroup(['id:1']);

        await component.toggleProcessed();

        expect(component.error).toBe(ACTION_ERROR);
        expect(overlay.rows.find((r) => r.id === 1).is_processed).toBe(false);
    });
});

describe('annotation popover — reply', () => {
    it('posts the reply, appends it to the thread and shows the author hint', async () => {
        const reply = { id: 99, body: '<p>Merci</p>', author_profile: { display_name: 'Alice' } };
        fetchMock.mockResolvedValue(jsonResponse(reply, 201));
        const refreshed = vi.fn();
        window.addEventListener('annotations:list-refreshed', refreshed);
        openGroup(['id:1']);
        component.startReply();
        replyBody('<p>Merci</p>');

        await component.sendReply();

        expect(fetchMock.mock.calls[0][0]).toBe('/comments/annotations/1/replies');
        expect(JSON.parse(fetchMock.mock.calls[0][1].body)).toEqual({ body: '<p>Merci</p>' });
        expect(component.current.replies.map((r) => r.id)).toEqual([99]);
        expect(refreshed.mock.calls[0][0].detail.list.items[0].replies.map((r) => r.id)).toEqual([99]);
        expect(component.replying).toBe(false);
        expect(component.showsHint(component.current)).toBe(true);
        window.removeEventListener('annotations:list-refreshed', refreshed);
    });

    it('keeps the typed text and shows the error on failure', async () => {
        fetchMock.mockResolvedValue(jsonResponse({}, 500));
        openGroup(['id:1']);
        component.startReply();
        replyBody('<p>Merci</p>');

        await component.sendReply();

        expect(component.replyError).toBe(ACTION_ERROR);
        expect(component.replying).toBe(true);
        expect(document.getElementById(`quill-editor-area-${REPLY_EDITOR_ID}`).value).toBe('<p>Merci</p>');
        expect(component.current.replies).toEqual([]);
    });

    it('refuses an empty or over-long reply without posting', async () => {
        openGroup(['id:1']);
        component.startReply();

        replyBody('<p> </p>');
        await component.sendReply();
        expect(component.replyError).toBe(REPLY_EMPTY);

        replyBody(`<p>${'a'.repeat(21)}</p>`);
        await component.sendReply();
        expect(component.replyError).toBe(REPLY_TOO_LONG);
        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('offers « Répondre » only on rows that allow it', () => {
        openGroup(['id:1']);

        expect(component.canReply(component.current)).toBe(true);
        expect(component.canReply({ ...component.current, can_reply: false })).toBe(false);
    });
});
