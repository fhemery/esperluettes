/**
 * Fetches an HTML fragment. On a redirect (CGU, expired session) navigates the
 * window to the destination and returns null; returns null on non-OK or network
 * error; otherwise returns the HTML. A 401 / 419 (expired session or CSRF; the
 * X-Requested-With header makes Laravel answer 401 instead of redirecting)
 * reloads the page so the server answers the full request, and returns null.
 */
export async function fetchFragment(url, options = {}, deps = {}) {
    const doFetch = deps.fetch ?? ((...a) => window.fetch(...a));
    const navigate = deps.navigate ?? ((to) => { window.location.assign(to); });
    const reload = deps.reload ?? (() => { window.location.reload(); });

    try {
        const response = await doFetch(url, {
            ...options,
            headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
        });
        if (response.redirected) {
            navigate(response.url);
            return null;
        }
        if (response.status === 401 || response.status === 419) {
            reload();
            return null;
        }
        if (!response.ok) return null;
        return await response.text();
    } catch (e) {
        return null;
    }
}
