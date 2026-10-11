import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { createOverlayStore } from './store.js';
import * as drafts from '../../comment-draft/index.js';

const USER_ID = 7;
const CHAPTER_ID = 42;
const ROOT_ID = 10;
const URL = '/comments/annotations/in-entity';

let store;
let fetchMock;

function serverItem(id, overrides = {}) {
    return {
        id,
        comment_id: ROOT_ID,
        author_id: USER_ID,
        body: `<p>server ${id}</p>`,
        highlighted_text: `passage ${id}`,
        prefix: '',
        suffix: '',
        is_processed: null,
        replies: [],
        ...overrides,
    };
}

function respondWith(lists) {
    fetchMock.mockResolvedValue({ ok: true, status: 200, json: async () => ({ lists }) });
}

function context(overrides = {}) {
    return {
        role: 'commenter',
        mode: 'pending',
        rootCommentId: ROOT_ID,
        entityType: 'chapter',
        entityId: CHAPTER_ID,
        userId: USER_ID,
        url: URL,
        ...overrides,
    };
}

function addDraft(highlighted) {
    return drafts.addAnnotation(USER_ID, 'chapter', CHAPTER_ID, { body: '<p>draft</p>', highlighted });
}

beforeEach(() => {
    localStorage.clear();
    window.commentDrafts = drafts;
    fetchMock = vi.fn();
    globalThis.fetch = fetchMock;
    store = createOverlayStore();
});

afterEach(() => {
    store.destroy();
    vi.restoreAllMocks();
});

describe('annotation overlay store — loading', () => {
    it('loads every server list for an author, tagged with comment id and source', async () => {
        respondWith([
            { comment_id: 10, viewer_role: 'author', items: [serverItem(1, { author_id: 3 })] },
            { comment_id: 11, viewer_role: 'author', items: [serverItem(2, { comment_id: 11, author_id: 4 })] },
        ]);

        await store.load(context({ role: 'author', mode: 'draft', rootCommentId: null }));

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock.mock.calls[0][0]).toBe(`${URL}?entity_type=chapter&entity_id=${CHAPTER_ID}`);
        expect(store.rows.map((r) => [r.id, r.comment_id, r.source])).toEqual([
            [1, 10, 'server'],
            [2, 11, 'server'],
        ]);
    });

    it('does not fetch for a commenter without a root comment', async () => {
        await store.load(context({ mode: 'draft', rootCommentId: null }));

        expect(fetchMock).not.toHaveBeenCalled();
        expect(store.rows).toEqual([]);
    });

    it('does not fetch without a role', async () => {
        await store.load(context({ role: null }));

        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('leaves rows empty when the fetch fails, without throwing', async () => {
        fetchMock.mockRejectedValue(new Error('network'));

        await expect(store.load(context({ role: 'author' }))).resolves.toBeUndefined();
        expect(store.rows).toEqual([]);
    });

    it('leaves rows empty on a non-ok response', async () => {
        fetchMock.mockResolvedValue({ ok: false, status: 500, json: async () => ({}) });

        await store.load(context({ role: 'author' }));
        expect(store.rows).toEqual([]);
    });
});

describe('annotation overlay store — commenter local merge', () => {
    it('draft mode: lists the drafts as rows', async () => {
        const draft = addDraft('un brouillon');

        await store.load(context({ mode: 'draft', rootCommentId: null }));

        expect(store.rows).toHaveLength(1);
        expect(store.rows[0]).toMatchObject({
            tempId: draft.tempId,
            source: 'draft',
            highlighted_text: 'un brouillon',
            author_id: USER_ID,
            is_processed: null,
        });
    });

    it('pending mode: applies edits, appends adds, hides pending deletes', async () => {
        respondWith([{ comment_id: ROOT_ID, viewer_role: 'commenter', items: [serverItem(1), serverItem(2), serverItem(3)] }]);
        drafts.setPendingEdit(USER_ID, 'chapter', CHAPTER_ID, 1, '<p>edited</p>');
        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 2);
        const addTempId = drafts.addPendingAnnotation(USER_ID, 'chapter', CHAPTER_ID, { body: '<p>new</p>', highlighted: 'ajout' });

        await store.load(context());

        expect(store.rows.map((r) => r.id ?? r.tempId)).toEqual([1, 3, addTempId]);
        expect(store.rows[0].body).toBe('<p>edited</p>');
        expect(store.rows[2]).toMatchObject({ source: 'pending-add', comment_id: ROOT_ID, highlighted_text: 'ajout' });
    });

    it('re-reads drafts on comment-drafts:annotations-changed', async () => {
        await store.load(context({ mode: 'draft', rootCommentId: null }));
        expect(store.rows).toHaveLength(0);

        const draft = addDraft('plus tard');
        expect(store.rows.map((r) => r.tempId)).toEqual([draft.tempId]);

        drafts.removeAnnotation(USER_ID, 'chapter', CHAPTER_ID, draft.tempId);
        expect(store.rows).toHaveLength(0);
    });

    it('re-reads pending changes on comment-drafts:annotation-changes-changed', async () => {
        respondWith([{ comment_id: ROOT_ID, viewer_role: 'commenter', items: [serverItem(1)] }]);
        await store.load(context());
        expect(store.rows.map((r) => r.id)).toEqual([1]);

        drafts.setPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 1);
        expect(store.rows).toHaveLength(0);

        drafts.undoPendingDelete(USER_ID, 'chapter', CHAPTER_ID, 1);
        expect(store.rows.map((r) => r.id)).toEqual([1]);
    });

    it('ignores change events of another entity', async () => {
        await store.load(context({ mode: 'draft', rootCommentId: null }));
        drafts.addAnnotation(USER_ID, 'chapter', 999, { body: '<p>x</p>', highlighted: 'ailleurs' });

        expect(store.rows).toHaveLength(0);
    });

    it('authors get no local rows', async () => {
        respondWith([]);
        addDraft('brouillon du lecteur');

        await store.load(context({ role: 'author' }));
        expect(store.rows).toEqual([]);
    });
});

describe('annotation overlay store — list refresh', () => {
    it('replaces one server list on annotations:list-refreshed', async () => {
        respondWith([
            { comment_id: 10, viewer_role: 'author', items: [serverItem(1, { author_id: 3 })] },
            { comment_id: 11, viewer_role: 'author', items: [serverItem(2, { comment_id: 11, author_id: 4 })] },
        ]);
        await store.load(context({ role: 'author' }));

        window.dispatchEvent(new CustomEvent('annotations:list-refreshed', {
            detail: { commentId: 11, list: { comment_id: 11, viewer_role: 'author', items: [serverItem(5, { comment_id: 11 })] } },
        }));

        expect(store.rows.map((r) => r.id)).toEqual([1, 5]);
    });
});

describe('annotation overlay store — visibleRows', () => {
    beforeEach(async () => {
        respondWith([
            { comment_id: 10, viewer_role: 'author', items: [serverItem(1, { author_id: 3, is_processed: false }), serverItem(2, { author_id: 3, is_processed: true })] },
            { comment_id: 11, viewer_role: 'author', items: [serverItem(3, { comment_id: 11, author_id: 4, is_processed: false })] },
        ]);
        await store.load(context({ role: 'author' }));
        store.enabled = true;
    });

    it('is empty until the viewer turns the display on', () => {
        store.enabled = false;
        expect(store.visibleRows).toEqual([]);

        store.enabled = true;
        expect(store.visibleRows.map((r) => r.id)).toEqual([1, 3]);
    });

    it('is switched on by a local annotation change of a commenter', async () => {
        const commenter = createOverlayStore();
        await commenter.load(context());
        expect(commenter.enabled).toBe(false);

        window.dispatchEvent(new CustomEvent('comment-drafts:annotations-changed', {
            detail: { entityType: 'chapter', entityId: CHAPTER_ID },
        }));

        expect(commenter.enabled).toBe(true);
        commenter.destroy();
    });

    it('follows the heat source live', () => {
        let heat = false;
        const live = createOverlayStore({ heatSource: () => heat });
        expect(live.heatOn).toBe(false);

        heat = true;
        expect(live.heatOn).toBe(true);
        expect(live.shown).toBe(false);
    });

    it('hides processed rows unless showProcessed', () => {
        expect(store.visibleRows.map((r) => r.id)).toEqual([1, 3]);

        store.filters.showProcessed = true;
        expect(store.visibleRows.map((r) => r.id)).toEqual([1, 2, 3]);
    });

    it('hides filtered commenters', () => {
        store.filters.hiddenCommenterIds.add(3);
        expect(store.visibleRows.map((r) => r.id)).toEqual([3]);
    });

    it('is empty while the heat map is on', () => {
        store.heatOn = true;
        expect(store.visibleRows).toEqual([]);

        store.heatOn = false;
        expect(store.visibleRows.map((r) => r.id)).toEqual([1, 3]);
    });
});
