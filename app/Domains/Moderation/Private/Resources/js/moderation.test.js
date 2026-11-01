import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import './moderation.js';

function mockFetch(status, body) {
    globalThis.fetch = vi.fn().mockResolvedValue({
        status,
        ok: status >= 200 && status < 300,
        redirected: false,
        json: async () => body,
        text: async () => '',
    });
}

describe('moderation report JS', () => {
    beforeEach(() => {
        window.history.replaceState({}, '', '/');
        document.head.innerHTML = '<meta name="csrf-token" content="t">';
        vi.stubGlobal('alert', vi.fn());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('shows the 403 message in the modal without redirecting', async () => {
        mockFetch(403, { success: false, message: 'Refused by guard' });
        const form = window.reportForm('annotation', 1);

        await form.submitReport();

        expect(form.errorMessage).toBe('Refused by guard');
        expect(window.location.pathname).not.toBe('/login');
        expect(form.submitting).toBe(false);
    });

    it('falls back to the generic message on a non-JSON 403', async () => {
        globalThis.fetch = vi.fn().mockResolvedValue({
            status: 403, ok: false, redirected: false,
            json: async () => { throw new Error('not json'); },
        });
        const form = window.reportForm('annotation', 1);

        await form.submitReport();

        expect(form.errorMessage).toBe('An error occurred.');
    });

    it('redirects to /login on 401 at submit', async () => {
        mockFetch(401, {});
        const form = window.reportForm('annotation', 1);

        await form.submitReport();

        expect(window.location.pathname).toBe('/login');
    });

    it('alerts on 403 at form load without redirecting', async () => {
        mockFetch(403, {});
        const button = window.reportButton('annotation', 1);

        await button.loadForm();

        expect(alert).toHaveBeenCalled();
        expect(window.location.pathname).not.toBe('/login');
    });

    it('redirects to /login on 401 at form load', async () => {
        mockFetch(401, {});
        const button = window.reportButton('annotation', 1);

        await button.loadForm();

        expect(window.location.pathname).toBe('/login');
    });
});
