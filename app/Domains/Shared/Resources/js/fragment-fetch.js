/**
 * Fetches an HTML fragment. On a redirect (CGU, expired session) navigates the
 * window to the destination and returns null; returns null on non-OK or network
 * error; otherwise returns the HTML.
 */
export async function fetchFragment(url, options = {}, deps = {}) {
    const doFetch = deps.fetch ?? ((...a) => window.fetch(...a));
    const navigate = deps.navigate ?? ((to) => { window.location.assign(to); });

    try {
        const response = await doFetch(url, {
            ...options,
            headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
        });
        if (response.redirected) {
            navigate(response.url);
            return null;
        }
        if (!response.ok) return null;
        return await response.text();
    } catch (e) {
        return null;
    }
}
