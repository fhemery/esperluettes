# Auth — compliant middleware stores XHR fragment URLs as intended — context

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### BUILD phase 1 → WRAP (2026-10-25)

As planned, no deviation. XHR with a missing/foreign Referer leaves any earlier `url.intended` untouched (A3).

### REFINE → BUILD (2026-10-25)

Fast path: §1–§3 written by the refiner, no question asked. Likeliest reversal:
A1 (Referer instead of plain dashboard fallback). Trap: the Referer is
client-supplied, so only a same-host one may be stored (open redirect). The
`routeIs('compliance.*')` guard must stay. One phase.

## 1. Functional spec

### 1.1 Overview

When a user who must accept the CGU or upload a parental authorization triggers
a script-loaded HTML fragment request (`X-Requested-With: XMLHttpRequest`), the
redirect to the compliance page stays, but the fragment URL must no longer
become the post-compliance destination. After compliance, the user returns to
the page they were viewing (or the dashboard if unknown).

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Fragment XHR | A request sent by `window.fetchFragment` with `X-Requested-With: XMLHttpRequest`, answered by a bare HTML snippet |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | N/A, middleware ignores guests | |
| `user` / `user-confirmed` | unchanged | Same compliance flow; only the return destination changes |
| Others | Same as above | |

### 1.4 Functional requirements

1. Non-compliant authenticated user sends a non-XHR request: unchanged (`fullUrl()` stored, redirect to compliance page).
2. Same user sends an XHR request: still redirected to the compliance page (`fetchFragment` relies on it); `url.intended` is never set to the request's URL.
3. For an XHR request carrying a `Referer` on the same host (and not a compliance route), that Referer is stored as `url.intended`; otherwise `url.intended` is left untouched.
4. After accepting terms / uploading the authorization, the user lands on `url.intended` if set, else the dashboard (existing behaviour).

### 1.5 Lifecycle

N/A — no data stored beyond the session key.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | See 1.3 |
| Visibility / privacy | Only same-host Referer is trusted (no open redirect) |
| Settings, Notifications, Events, Statistics, Moderation, Media, Search | N/A |
| Lifecycle / cascade | N/A |
| i18n, Mobile, Accessibility | N/A — no UI change |

### 1.7 Out of scope

Client-side changes to `fetchFragment`; other middleware or login-intended flows.

### 1.8 Open questions

None blocking.

## 2. Architecture

### 2.1 Domain placement

Auth only: `EnsureUserCompliance` (`app/Domains/Auth/Public/Middleware/`).

### 2.2 Data model

None.

### 2.3 PHP architecture

Replace the two duplicated `session()->put('url.intended', ...)` blocks by one
private method `rememberIntended(Request $request): void`:

- return if `$request->routeIs('compliance.*')`;
- if `$request->ajax()`: take `$request->headers->get('referer')`; store it only
  if its host equals `$request->getHost()`; else store nothing;
- otherwise store `$request->fullUrl()`.

### 2.4 Frontend

None.

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Pest integration tests in `app/Domains/Auth/Tests/Feature/ComplianceTest.php`:
XHR without referer (no intended, redirect kept), XHR with same-host referer
(intended = referer, accept redirects there), XHR with foreign referer (ignored),
non-XHR unchanged (accept redirects to the original URL).

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Where to go after CGU for XHR | Dashboard only; Referer | Same-host Referer, else dashboard | Better UX for a few lines; flips if Referer proves unreliable |

### 2.8 Risks acknowledged

Referer may be stripped by policy: falls back to dashboard, harmless.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Middleware fix + tests | S | — | DONE |

### Phase 1 — Middleware fix + tests

**Goal.** XHR requests never store their own URL as intended. **Reads.** §2.3, §2.6.

**Deliverables.** `EnsureUserCompliance.php` (private `rememberIntended`), new tests in `ComplianceTest.php`.

**Tests.** The four cases of §2.6, failing first.

**Acceptance.**
- ✅ XHR request redirects to compliance page without storing its URL.
- ✅ Non-XHR behaviour unchanged.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Fanart tab as non-compliant user | After accepting CGU, back on the story page, not raw HTML | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Use a same-host Referer for XHR rather than the bare dashboard fallback (request left it open) | REFINE | Yes, drop the referer branch |
| A2 | Detect XHR with `$request->ajax()` only (not `expectsJson()`), matching `fetchFragment`'s header | REFINE | Yes |
| A3 | Foreign or missing Referer: store nothing (a stale earlier `url.intended` is left as is) | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE: `rememberIntended` in EnsureUserCompliance + 4 Pest tests in ComplianceTest (gate green).
