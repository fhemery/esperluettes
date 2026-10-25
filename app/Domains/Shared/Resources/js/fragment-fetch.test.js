import { describe, it, expect, vi } from 'vitest';
import { fetchFragment } from './fragment-fetch.js';

function res(over = {}) {
    return { ok: true, redirected: false, url: '/x', text: () => Promise.resolve('<p>hi</p>'), ...over };
}

describe('fetchFragment', () => {
    it('returns the body text on a 200 response', async () => {
        const fetch = vi.fn().mockResolvedValue(res());
        const navigate = vi.fn();
        expect(await fetchFragment('/x', {}, { fetch, navigate })).toBe('<p>hi</p>');
        expect(navigate).not.toHaveBeenCalled();
    });

    it('sends the X-Requested-With header and keeps caller headers', async () => {
        const fetch = vi.fn().mockResolvedValue(res());
        await fetchFragment('/x', { headers: { Accept: 'text/html' }, method: 'GET' }, { fetch });
        expect(fetch).toHaveBeenCalledWith('/x', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        });
    });

    it('navigates to response.url and returns null when the response was redirected', async () => {
        const fetch = vi.fn().mockResolvedValue(res({ redirected: true, url: '/login' }));
        const navigate = vi.fn();
        expect(await fetchFragment('/x', {}, { fetch, navigate })).toBeNull();
        expect(navigate).toHaveBeenCalledWith('/login');
    });

    it.each([403, 404, 500])('returns null without navigating on a %i response', async (status) => {
        const fetch = vi.fn().mockResolvedValue(res({ ok: false, status }));
        const navigate = vi.fn();
        expect(await fetchFragment('/x', {}, { fetch, navigate })).toBeNull();
        expect(navigate).not.toHaveBeenCalled();
    });

    it('returns null without navigating when fetch throws', async () => {
        const fetch = vi.fn().mockRejectedValue(new Error('net'));
        const navigate = vi.fn();
        expect(await fetchFragment('/x', {}, { fetch, navigate })).toBeNull();
        expect(navigate).not.toHaveBeenCalled();
    });
});
