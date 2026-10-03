import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationsModal, MODAL_NAME } from './modal.js';

const LABEL_ONE = '1 annotation';
const LABEL_MANY = '__COUNT__ annotations';
const LOAD_ERROR = 'Impossible de charger';
const ACTION_ERROR = 'Action impossible';
const CSRF = 'csrf-token-value';

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
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
    component = null;
    vi.unstubAllGlobals();
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
