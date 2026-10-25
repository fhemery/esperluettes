# Shared — adopt fetchFragment in other domains' fragment loaders — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).
>
> A section still reading `_Not written yet._` has not been done — that is how
> `/continue-task` knows where the task stands.

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### REFINE → BUILD (2026-10-25)

- Zero questions asked (auto). §2 and §3 written by the refiner (fast path); go straight to BUILD, one phase.
- Only 4 of the 6 listed candidate groups qualify; the other two are public routes or already handle the failure (see §1.4 table). Do not "complete" the list by converting them.
- Trap: `fetchFragment` returns text only, so call sites that read response headers (Notification load-more reads `X-Has-More`) cannot adopt it without changing the helper. Left out on purpose.
- Likeliest to be reversed: Comment list and Search dropdown excluded (A1, A2); Settings keeps the already-switched tab on `null` (A4).
- No PHP, no route, no i18n; only inline Alpine in 4 Blade files plus a doc line in Shared.

### BUILD phase 1 → WRAP (2026-10-25)

- Shipped as planned, no deviation. No automated test covers the inline Alpine; browser check (§3.2) not done.

## 1. Functional spec

### 1.1 Overview

Four lazily loaded HTML widgets still use a raw `fetch` and inject whatever
comes back. A user whose CGU were reset, or whose session expired, gets a whole
page injected in the widget. They switch to Shared's `window.fetchFragment`,
which navigates on a redirect, reloads on 401 / 419 and returns `null` on any
other failure; the widget then injects nothing.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Fragment loader | Client code that fetches server-rendered HTML and injects it into a widget (as opposed to a JSON call) |

### 1.3 Roles & visibility

No change to who sees what. All four adopted widgets are behind `auth`
(Settings: `role:user,user-confirmed`) and are never rendered for guests.

### 1.4 Functional requirements

Audit of every candidate (and of every other `fetch(` in `app/Domains`):

| Site | Verdict | Reason |
|------|---------|--------|
| Settings tabs (`Settings/.../pages/index.blade.php`) | **Adopt** | HTML; route needs a user role, page not rendered for guests. `null` → keep current content, do not push the URL. |
| Story admin moderation chapters (`Story/.../admin/moderation/index.blade.php`) | **Adopt** | HTML; `auth` + moderator role, admin page. `null` → not opened, loading cleared. |
| Moderation user-management search (`Moderation/.../admin/user-management.blade.php`, `onSearchInput`) | **Adopt** | HTML; `auth` + `compliant` + moderator role. `null` → results stay empty. Its POST activate/deactivate calls are JSON-ish status calls: out. |
| Profile user search (`Profile/.../components/user-search-input.blade.php`) | **Adopt** | HTML; `/profile/search` is `auth` + `compliant`; used on admin pages only. `null` → results emptied, dropdown closed. |
| Comment lazy list (`Comment/.../comment-list.blade.php`) | Exclude | HTML but `/comments/fragments` is public (`web` only): no redirect, no 401 possible; rendered for guests. Nothing to gain. |
| Search dropdown (`Search/.../header-search.blade.php`) | Exclude | HTML but `/search/partial` is public, widget shown to guests (`navigation-guest`). Nothing to gain. |
| Moderation report form (`Moderation/.../js/moderation.js` `loadForm`) | Exclude | Report button is rendered for guests on public stories/chapters and already handles redirect and 401/403 by sending to `/login`; a reload would replace that with a dead click. Report POST is JSON. |
| Notification load-more (`Notification/.../pages/index.blade.php`) | Exclude | HTML but reads the `X-Has-More` response header, which the helper does not expose. |
| ReadList chapters / load-more, Story profile-comments, Quote, Comment annotations, Profile role/user lookups, Jardino, Discord, Config/Settings parameter rows, image-field, reorderables, like/read toggles | Exclude | JSON, `text/plain` or POST/PUT/DELETE calls. |

Behaviour for each adopted site:
1. The widget calls the helper with its existing URL and headers.
2. Redirect (CGU) → window navigates to the destination; nothing injected.
3. 401 / 419 → page reloads; nothing injected.
4. Any other non-OK or network error → nothing injected, no visible error (as today for Settings/Story; Moderation/Profile search previously injected the error body).

### 1.5 Lifecycle

N/A — no data. Deactivated or deleted user: the request fails as today; now
yields a redirect/reload instead of an injected page.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — unchanged. `user` vs `user-confirmed` unaffected. |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A — report widget deliberately untouched (see table). |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A — the header search is excluded. |
| i18n | N/A — no new text (silent on failure, per request). |
| Mobile | N/A |
| Accessibility | N/A |

Reload loop safety: each adopted widget is absent from the page a guest gets
after a reload, so no loop (checked per site in the table).

### 1.7 Out of scope

- Changing the `compliant` middleware (see `auth-compliant-xhr-intended/`).
- Extending `fetchFragment` to expose response headers.
- Any JSON call; Notification load-more.
- Showing an error message on `null`.

### 1.8 Open questions

None blocking. Non-blocking: should `fetchFragment` later return headers so Notification load-more can adopt it? (backlog candidate, not this task).

## 2. Architecture

### 2.1 Domain placement

Each call site stays in its own domain (Settings, Story, Moderation, Profile);
Shared already owns the helper. No new code in Shared except one doc line.

### 2.2 Data model

None.

### 2.3 PHP architecture

None.

### 2.4 Frontend

Contract: `window.fetchFragment(url, options?) : Promise<string|null>`
(registered in Shared `app.js`; loaded on every layout). Each site replaces its
`fetch` + `res.text()` with `const html = await window.fetchFragment(url, opts)`
and treats `null` as "inject nothing":

- Settings tab: `if (html === null) return;` before assigning content and `history.pushState` (the surrounding `finally` still clears `loading`). Pass the existing `Accept: text/html` header; drop the explicit `X-Requested-With` (the helper sends it).
- Story chapters toggle: `.then(h => { if (h !== null) { html = h; open = true; } loading = false; })`.
- User-management search: `resultsHtml = html ?? ''`.
- Profile user search: `const html = await window.fetchFragment(...) ?? ''; results.innerHTML = html; open = html.trim() !== ''`.

### 2.5 Deptrac

No new edge (global on `window`, no PHP).

### 2.6 Testing strategy

No new test shape: the helper is already covered by `fragment-fetch.test.js`.
Existing PHP view tests must still pass (gate). Optional VERIFY: Settings tab
switch and Story admin chapter expand still work in a browser as a moderator.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Convert public-route widgets (Comment, Search)? | convert / leave | leave | No failure mode to fix; flips if those routes gain `auth`/`compliant`. |
| 2 | Report form | convert / leave | leave | Guest-visible button; reload would regress the `/login` redirect. |

### 2.8 Risks acknowledged

An inline script on a page without the Shared layout would lack
`window.fetchFragment`; all four sites use `x-app-layout`/admin layouts that load Shared `app.js`. Trigger to revisit: a TypeError in console on any of the four.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Adopt `fetchFragment` in the four loaders | S | — | DONE |

### Phase 1 — Adopt `fetchFragment` in the four loaders

**Goal.** Replace raw `fetch` + inject with `window.fetchFragment` at the four adopted sites. **Reads.** §1.4, §2.4.

**Deliverables.**
- `app/Domains/Settings/Private/Resources/views/pages/index.blade.php`
- `app/Domains/Story/Private/Resources/views/pages/admin/moderation/index.blade.php`
- `app/Domains/Moderation/Private/Resources/views/pages/admin/user-management.blade.php` (`onSearchInput` only)
- `app/Domains/Profile/Private/Resources/views/components/user-search-input.blade.php`
- `app/Domains/Shared/AGENTS.md`: reword "Many older call sites…" to say the remaining raw `fetch` HTML loaders are deliberate (public routes, report form, header-reading load-more).

**Tests.** None new; run existing Settings, Story, Moderation, Profile suites.

**Acceptance.**
- ✅ The four sites no longer call raw `fetch` for HTML and inject nothing on `null`.
- ✅ Excluded sites untouched.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Settings | Switching tabs loads content and updates URL | |
| Story admin moderation | Chapters expand/collapse | |
| Moderation user management | Search returns results | |
| Activation-code create page | Sponsor search shows results | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Comment lazy list excluded: public route, no auth/compliant, nothing to fix | REFINE | Yes |
| A2 | Search dropdown excluded: public route, shown to guests | REFINE | Yes |
| A3 | Moderation report form excluded: guest-visible button already redirects to `/login` | REFINE | Yes |
| A4 | Settings on `null`: keep current content, no URL push, silent | REFINE | Yes |
| A5 | Notification load-more excluded (needs `X-Has-More` header); helper not extended | REFINE | Yes |
| A6 | Silent on failure (no new error text), per the request | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE: four loaders adopt `window.fetchFragment`; Shared AGENTS.md line reworded; gate green.
