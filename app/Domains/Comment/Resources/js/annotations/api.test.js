import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { deleteReply, postReply, saveAnnotationChanges } from './api.js';

const CSRF = 'csrf-token-value';

let fetchMock;

function jsonResponse(body, status = 200) {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: async () => body,
        text: async () => JSON.stringify(body),
    };
}

function lastRequest() {
    const [url, init] = fetchMock.mock.calls.at(-1);
    return { url, init, body: init.body ? JSON.parse(init.body) : undefined };
}

describe('annotations api', () => {
    beforeEach(() => {
        document.head.innerHTML = `<meta name="csrf-token" content="${CSRF}">`;
        fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    describe('saveAnnotationChanges', () => {
        const changes = {
            adds: [{ tempId: 'tmp-1', body: '<p>❤️</p>', highlighted: 'passage', prefix: 'before ', suffix: ' after' }],
            edits: { 12: '<p>edited</p>', 15: '<p>other</p>' },
            deletes: [13, 14],
        };

        it('maps the slot to the PUT body and resolves the refreshed list on 200', async () => {
            const list = { comment_id: 10, viewer_role: 'commenter', items: [] };
            fetchMock.mockResolvedValue(jsonResponse(list));

            const result = await saveAnnotationChanges(10, changes);

            const { url, init, body } = lastRequest();
            expect(url).toBe('/comments/10/annotations');
            expect(init.method).toBe('PUT');
            expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);
            expect(init.headers['Accept']).toBe('application/json');
            expect(body).toEqual({
                adds: [{ key: 'tmp-1', body: '<p>❤️</p>', highlighted_text: 'passage', prefix: 'before ', suffix: ' after' }],
                edits: [{ id: 12, body: '<p>edited</p>' }, { id: 15, body: '<p>other</p>' }],
                deletes: [13, 14],
            });
            expect(result).toEqual({ ok: true, list });
        });

        it('resolves with the errors object on 422', async () => {
            const errors = { 'deletes.13': ['Cette annotation n’existe plus.'] };
            fetchMock.mockResolvedValue(jsonResponse({ message: 'invalid', errors }, 422));

            await expect(saveAnnotationChanges(10, changes)).resolves.toEqual({ ok: false, status: 422, errors });
        });

        it('resolves ok:false with status 404 when the root comment is gone', async () => {
            fetchMock.mockResolvedValue(jsonResponse({ message: 'Not Found' }, 404));

            await expect(saveAnnotationChanges(10, changes)).resolves.toMatchObject({ ok: false, status: 404 });
        });

        it('throws on any other failure', async () => {
            fetchMock.mockResolvedValue(jsonResponse({ message: 'boom' }, 500));

            await expect(saveAnnotationChanges(10, changes)).rejects.toThrow();
        });
    });

    it('postReply POSTs the body and returns the created reply', async () => {
        const reply = { id: 30, body: '<p>merci</p>' };
        fetchMock.mockResolvedValue(jsonResponse(reply, 201));

        await expect(postReply(12, '<p>merci</p>')).resolves.toEqual(reply);

        const { url, init, body } = lastRequest();
        expect(url).toBe('/comments/annotations/12/replies');
        expect(init.method).toBe('POST');
        expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);
        expect(body).toEqual({ body: '<p>merci</p>' });
    });

    it('deleteReply sends a DELETE and throws when refused', async () => {
        fetchMock.mockResolvedValue({ ok: true, status: 204, text: async () => '' });
        await deleteReply(30);

        const { url, init } = lastRequest();
        expect(url).toBe('/comments/annotations/replies/30');
        expect(init.method).toBe('DELETE');
        expect(init.headers['X-CSRF-TOKEN']).toBe(CSRF);

        fetchMock.mockResolvedValue(jsonResponse({ message: 'Forbidden' }, 403));
        await expect(deleteReply(30)).rejects.toThrow();
    });
});
