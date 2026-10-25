# Fanart — lazy fragments inject a full page on redirect — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

One small helper in Shared JS, used by the four Fanart call sites. Shared, not Fanart, because the inline Alpine objects on the story and profile pages cannot import modules: they reach helpers through `window` (precedent: `window.DateUtils`), and Shared `app.js` is always loaded whereas `fanart-viewer.js` only loads with the viewer modal. Other domains with the same pattern can adopt it later.

## 2. Data model

None.

## 3. PHP architecture

None.

## 4. Frontend architecture

`fetchFragment(url, options)` in `Shared/Resources/js/fragment-fetch.js`, exposed as `window.fetchFragment`:
- sends the `X-Requested-With: XMLHttpRequest` header;
- if `response.redirected`, sets `window.location` to `response.url` and returns `null`;
- if the status is 401 or 419 (expired session / CSRF), reloads the page (injectable `deps.reload`) and returns `null`;
- if `!response.ok`, returns `null`;
- otherwise returns the response text.
Network errors are caught and return `null`.
Callers treat `null` as "inject nothing" (tab / group / profile story: html stays empty; viewer: closes, keeping its stale-request guard). The location assignment and `fetch` are injectable so vitest can test without a browser.

## 5. Deptrac

None (JS only).

## 6. Testing strategy

Vitest for the helper (ok, redirected, 403, network error). Blade views are exercised by existing PHP fragment tests; the four call sites are checked in VERIFY by redirecting a fragment request in the real app.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Helper location | local to each call site; Fanart module; Shared helper on `window` | Shared helper | four sites, inline Alpine cannot import, other domains can adopt it |
| 2 | On redirect | navigate to `response.url`; show an error state | navigate | the reader is actually sent where the server wanted; no new text needed |
