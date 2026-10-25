# Fanart — lazy fragments inject a full page on redirect — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Shared `fetchFragment` helper (shared infrastructure) | S | — | DONE |
| 2 | Adopt `fetchFragment` in the four Fanart call sites | S | 1 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

No checkpoint row: phase 1 adds a new helper with no existing consumer, and
phase 2 is the feature itself, checked by VERIFY.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Stay on branch `fix/misc-small-fixes` (user instruction).

---

## Phase 1 — Shared `fetchFragment` helper

**Goal.** Provide one JS helper that fetches an HTML fragment and, when the
server redirected, navigates the window to the destination instead of
returning the destination's HTML (architecture §1, §4).

**Deliverables.**
- `app/Domains/Shared/Resources/js/fragment-fetch.js` (new), exporting:
  ```js
  export async function fetchFragment(url, options = {}, deps = {})
  // deps.fetch    default (...a) => window.fetch(...a)
  // deps.navigate default (to) => { window.location.assign(to) }
  ```
  Behaviour:
  - calls `fetch(url, { ...options, headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers } })`;
  - `response.redirected` → `deps.navigate(response.url)`, return `null`;
  - `!response.ok` → return `null`;
  - otherwise → return `await response.text()`;
  - any thrown error (network) → return `null`.
  The `deps` parameter mirrors the injectable-deps pattern already used by
  `app/Domains/Fanart/Private/Resources/js/fanart-player.js`.
- `app/Domains/Shared/Resources/js/app.js`: `import { fetchFragment } from './fragment-fetch.js';`
  and `window.fetchFragment = fetchFragment;` next to `window.DateUtils`.
- `app/Domains/Shared/README.md`: add a row to the JS helpers table (around
  line 186, next to `date-utils.js`):
  `| fragment-fetch.js | window.fetchFragment | Fetches an HTML fragment (sends X-Requested-With); on a redirect navigates the window to the destination and returns null; returns null on non-OK or network error, else the HTML. |`

**Tests.**
- `app/Domains/Shared/Resources/js/fragment-fetch.test.js` (vitest, fake
  `fetch` and `navigate` through `deps`):
  - `returns the body text on a 200 response`
  - `sends the X-Requested-With header and keeps caller headers`
  - `navigates to response.url and returns null when the response was redirected`
  - `returns null without navigating on a 403 / 404 / 500 response`
  - `returns null without navigating when fetch throws`

**Acceptance.**
- ✅ The five vitest cases above pass.
- ✅ `window.fetchFragment` is defined by Shared `app.js`.
- ✅ No Fanart file is changed in this phase.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Adopt `fetchFragment` in the four Fanart call sites

**Goal.** Every lazily loaded Fanart fragment goes through the Shared helper,
so a redirected request (CGU not accepted, expired session) navigates the
window and injects nothing, and any other failure injects nothing
(functional §4.1, §4.2; architecture §4).

Builds on phase 1: `app/Domains/Shared/Resources/js/fragment-fetch.js` exports
`fetchFragment(url, options = {}, deps = {})`, exposed as
`window.fetchFragment` by Shared `app.js`; it returns the HTML string on
success, and `null` on redirect (after navigating), non-OK status or network
error. It already sends `X-Requested-With`.

**Deliverables.** Replace each raw `fetch` + `response.text()` with the helper;
`null` means "inject nothing".
- `app/Domains/Fanart/Private/Resources/views/components/story-tabs.blade.php`
  (`loadFanarts`, ~line 15):
  `this.html = (await window.fetchFragment(@js($tabUrl))) ?? '';`
- `app/Domains/Fanart/Private/Resources/views/fragments/tab.blade.php`
  (chapter group `load()`, ~line 41): same pattern on
  `this.$root.dataset.groupUrl`. This site currently injects any body,
  including error pages — it now injects nothing on non-OK (functional §4.2).
- `app/Domains/Fanart/Private/Resources/views/components/profile-tab.blade.php`
  (`toggle()`, ~line 26): same pattern on `this.$root.dataset.groupUrl`.
- `app/Domains/Fanart/Private/Resources/js/fanart-viewer.js` (`open(url)`):
  `import { fetchFragment } from '../../../../Shared/Resources/js/fragment-fetch.js';`
  (an ES module, so it imports rather than reading `window`), then:
  ```js
  const html = await fetchFragment(url);
  if (requestId !== this.requestId) return;
  if (html === null) { this.close(); return; }
  this.html = html;
  ```
  The try/catch goes (the helper never throws); the stale-request guard stays.

**Tests.**
- No new PHP test: the Blade changes are inline Alpine and the existing
  fragment feature tests (`app/Domains/Fanart/Tests/Feature/StoryPageFanartTest.php`,
  `ChapterPageFanartTest.php`, the tab / group / profile fragment tests) must
  stay green — they prove the markup still renders.
- Browser behaviour is checked in VERIFY (Visual QA checklist below), per
  architecture §6.

**Acceptance.**
- ✅ `grep -rn "fetch(" app/Domains/Fanart/Private/Resources/views app/Domains/Fanart/Private/Resources/js/fanart-viewer.js`
  shows no raw `fetch(` call left (only `fetchFragment(`).
- ✅ The four sites assign `''` (Blade) or close the modal (viewer) when the
  helper returns `null`.
- ✅ Existing Fanart PHP tests and `fanart-player.test.js` still pass.
- ✅ `pnpm run gate` green (full, not `--quick`, since a Vite entry changed).

---

## Visual QA checklist

Filled by VERIFY. To provoke the redirect: log in as a confirmed user, open a
story page with fanarts, then in another tab either log out (expired session →
login) or reset that user's CGU acceptance (`compliant` → CGU page), and come
back to the first tab without reloading.

| Surface | Check | OK? |
|---------|-------|-----|
| Story page, Fanarts tab — redirected | Clicking the Fanarts tab navigates the window to the login / CGU page; no page-within-the-page | |
| Fanarts tab, collapsed chapter group — redirected | Load the tab while valid, then invalidate; "Show all" navigates to login / CGU, nothing injected in the group | |
| Viewer modal — redirected | Load the tab while valid, then invalidate; clicking a fanart navigates to login / CGU, modal does not show the page | |
| Profile Fanarts tab, story group — redirected | On a profile with fanarts, invalidate then expand a story: navigates to login / CGU, nothing injected | |
| Story page, Fanarts tab — normal | Valid confirmed user: tab, chapter groups, viewer and profile groups load as before | |
| Viewer — deep link | `?tab=fanarts&fanart={id}` still opens the tab and the viewer | |
| Viewer — 404 | Deep link with a non-existent fanart id: modal closes, no error page injected | |
| Mobile (375 px) | Normal path on the story Fanarts tab and the viewer unchanged | |

## Open items

- After accepting the CGU, `compliant` sends the user back to the *fragment
  URL* (raw HTML) because it stored it as the intended destination. Out of
  scope (functional §8–9); to report at WRAP. Not blocking any phase.
- The same raw-`fetch` injection pattern exists in other domains (Comment,
  Search, Settings, Moderation…); they can adopt `window.fetchFragment` later.
  Out of scope; to report at WRAP.
