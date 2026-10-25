# Fanart — lazy fragments inject a full page on redirect

**Status:** DONE — 2026-10-25 · **Domain(s):** `Shared`, `Fanart`

## What it does

The four lazily loaded Fanart fragments (story Fanarts tab, chapter groups,
profile-tab story groups, viewer modal) sit behind `auth` + `compliant`. A raw
`fetch()` followed the CGU redirect and injected the whole CGU page into the
tab / modal. They now go through a new Shared helper, `fetchFragment`, which
navigates the window on a redirect, reloads the page on 401 / 419, and returns
`null` (= inject nothing) on any other failure.

## Key behaviour

- `fetchFragment(url, options = {}, deps = {})`: always sends
  `X-Requested-With: XMLHttpRequest` (caller headers merged after it).
- `response.redirected` → `window.location.assign(response.url)`, returns `null`
  (CGU / parental-consent path of `compliant`).
- 401 / 419 → `window.location.reload()`, returns `null`. Needed because, with
  the XHR header, Laravel's `auth` answers a guest **401, not 302 /login** — the
  design assumed a redirect; VERIFY found it (phase 3 added the reload).
- Other non-OK (403/404/5xx) or network error → `null`, never throws.
- No reload loop: Fanart tabs / viewer / profile tab are not rendered for guests
  (`seeFeature` / `seeProfileTab` return false), so the reloaded page has no
  fragment to fetch. Any new adopter must check the same.
- Chapter group (`fragments/tab.blade.php`) used to inject any body, including
  error pages; it now injects nothing on non-OK like the other sites.
- Viewer keeps its stale-request guard; `null` closes the modal.

## Where the code lives

| Concern | Path |
|---------|------|
| Helper | `app/Domains/Shared/Resources/js/fragment-fetch.js` |
| Global `window.fetchFragment` | `app/Domains/Shared/Resources/js/app.js` |
| Inline Alpine call sites | `app/Domains/Fanart/Private/Resources/views/components/story-tabs.blade.php`, `.../components/profile-tab.blade.php`, `.../fragments/tab.blade.php` |
| Viewer (ES module, imports the helper directly) | `app/Domains/Fanart/Private/Resources/js/fanart-viewer.js` |
| Tests | `app/Domains/Shared/Resources/js/fragment-fetch.test.js` (vitest, injectable `fetch` / `navigate` / `reload`) |

## Extension points used

None. No PHP, no route, no deptrac edge, no i18n string.

## Decisions worth remembering

- Shared helper on `window`, not local to Fanart: inline Alpine objects cannot
  import modules (precedent `window.DateUtils`), and other domains can adopt it.
- On redirect, navigate to the destination rather than show an error state
  (no new text needed).
- `compliant` middleware left untouched (out of scope by request).
- Assumptions made without asking (all reversible): navigate rather than error;
  chapter group also ignores non-OK; only the four Fanart sites adopt the
  helper; middleware "intended" defect reported, not fixed; 401/419 reloads the
  page.

## Verification limits

- VERIFY used a temporary Playwright spec (deleted), no spec kept in
  `e2e/tests/features/`, nothing to retire.
- CGU reset was simulated by nulling `terms_accepted_at` and clearing the
  session's compliance cache key — not through an admin CGU-version bump.
- Expired session (cookies cleared, then tab clicked): the page reloads and lands
  on the **guest story page, not the login page**, because stories are public;
  the Fanart tabs simply vanish. Plan/spec §4.1 said "login page"; the code does
  a reload, which is what the user actually sees.
- Parental-consent redirect not exercised (same code path as CGU).

## Not done

- After accepting the CGU, `compliant` sends the user to the stored intended
  URL, which is the **fragment URL** (raw HTML) →
  [`auth-compliant-xhr-intended`](../auth-compliant-xhr-intended/00-request.md)
  (proposal: do not set `url.intended` for XHR requests).
- Other domains with the same raw fetch+inject pattern (Comment lazy list,
  Search dropdown, Settings tabs, Moderation, Story admin moderation, Profile
  user search) →
  [`shared-fetch-fragment-adoption`](../shared-fetch-fragment-adoption/00-request.md).
