import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createAggregateStore } from './aggregate-store.js';

function jsonResponse(body) {
    return { ok: true, json: async () => body };
}

describe('aggregate store', () => {
    beforeEach(() => {
        globalThis.fetch = vi.fn(async () => jsonResponse({ items: [{ id: 1 }], total_count: 1 }));
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('starts hidden and never writes to localStorage', async () => {
        const setItem = vi.spyOn(Storage.prototype, 'setItem');
        const store = createAggregateStore();

        expect(store.visible).toBe(false);

        await store.toggle(7);
        await store.toggle(7);

        expect(store.visible).toBe(false);
        expect(setItem).not.toHaveBeenCalled();
    });

    it('fetches nothing until the heat is first turned on', async () => {
        const store = createAggregateStore();

        expect(globalThis.fetch).not.toHaveBeenCalled();
        expect(store.rows).toEqual([]);
    });

    it('fetches the aggregate only once across repeated toggles', async () => {
        const store = createAggregateStore();

        await store.toggle(7);
        await store.toggle(7);
        await store.toggle(7);

        expect(globalThis.fetch).toHaveBeenCalledTimes(1);
        expect(store.rows).toEqual([{ id: 1 }]);
        expect(store.totalCount).toBe(1);
        expect(store.visible).toBe(true);
    });

    it('keeps the server-seeded count when the fetch fails', async () => {
        globalThis.fetch = vi.fn(async () => ({ ok: false, text: async () => 'boom' }));
        const store = createAggregateStore();
        store.totalCount = 4;

        await store.toggle(7);

        expect(store.rows).toEqual([]);
        expect(store.totalCount).toBe(4);
        expect(store.loaded).toBe(true);
    });
});
