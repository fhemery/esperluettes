# Moderation — report refusals: 403 handling and moderator-only accounts — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes.

**Complexity:** 2 /5 · **Mode:** interactive

## 0. Handoff

### BUILD phase 1 → WRAP (2026-11-01)

- A role-middleware refusal on these routes is a 302 redirect (not 403), so the Pest "no role" case asserts a redirect and no stored report.

### DESIGN+PLAN → BUILD (2026-11-01)

- Written inline by the refiner (fast path). One phase, JS fix + route gate + tests.
- Assumption most likely reversed: moderators are *allowed* to report (route gate widened) rather than the button hidden. Both the comment list and the annotation hint show the button to moderators today, so widening is the smaller change.
- Verified: comment-list report button shows to every non-author viewer, so a moderator-only account hits the same refusal there today; the fix covers every topic.
- `store()` already returns `{success:false, message}` on 403 (guard refusal); only the JS discards it. A role-middleware 403 on the form GET is HTML, not JSON.

### REFINE → DESIGN+PLAN (2026-11-01)

0 blocking questions asked. See §4 assumptions.

## 1. Functional spec

### 1.1 Overview

Make « Signaler » behave for legitimate users when a report is refused. A 403
refusal (topic guard disagreeing with the `can_report` hint) shows its message
in the report modal instead of redirecting to `/login`; 401 still redirects.
Accounts holding only a moderation/admin role can submit reports like anyone
else.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Refus de signalement | A 403 from the report endpoints: the `ReportGuardInterface` of the topic refuses, or the role gate does. |
| Compte modérateur seul | Account with `moderator` / `admin` / `tech-admin` but no `user` / `user-confirmed` role. |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | No button; a 401 on the endpoint redirects to `/login` | — |
| `user` / `user-confirmed` | « Signaler » (unchanged) | Report (unchanged) |
| Moderator / admin / tech-admin (with or without `user*`) | « Signaler » (unchanged) | Report, even without a `user*` role (new) |

Who may report which entity per topic is unchanged (topic guards).

### 1.4 Functional requirements

1. Form load (`GET moderation/report-form/...`): on 401, redirect to `/login`; on 403, show an error message (no redirect); other errors keep the current alert.
2. Submit (`POST moderation/report`): on 401, redirect to `/login`; on 403, show the response `message` in the modal's error area and keep the modal open (nothing stored); if the body has no message, show the generic report error.
3. The `moderation/report-form` and `moderation/report` routes accept moderator, admin and tech-admin accounts in addition to `user` and `user-confirmed`.

### 1.5 Lifecycle

N/A — no data stored by this change.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | See 1.3; route gate widened to staff roles. |
| Visibility / privacy | Refusal message is the guard's own `message`; no new information exposed. |
| Settings | N/A |
| Notifications | N/A — a staff reporter triggers the existing staff notification as any reporter. |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | This is the moderation flow; applies to every topic. |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | Reuse existing `moderation::report.error` for the generic fallback; no new copy expected. |
| Mobile | N/A — same modal. |
| Accessibility | Error shown in the modal's existing error region. |

### 1.7 Out of scope

- Changing who may report what on the `annotation` topic (or any topic).
- Hiding the report button for moderators.
- Changing the `can_report` hint computation.

### 1.8 Open questions

None blocking.

## 2. Architecture

- Domain: Moderation only. No new table, API, event or deptrac edge.
- Route gate (`Moderation/Private/routes.php`): the report group's `role:` middleware gains `Roles::MODERATOR`, `Roles::ADMIN`, `Roles::TECH_ADMIN` (same list as the staff group below it). Auth + `compliant` stay.
- JS (`Moderation/Private/Resources/js/moderation.js`): in `submitReport`, 401 → `/login`; 403 → fall through to the existing JSON handling (`errorMessage = data.message`, fallback to the generic string). In `loadForm`, 401 → `/login`; 403 → `alert` of the generic error. Parse JSON defensively (a role-gate 403 might not be JSON): on parse failure use the generic message (the existing catch already does).
- Tests: Pest integration for the route gate; vitest for the two JS paths (`moderation.test.js` beside the script, happy-dom, mocked `fetch`/`location`).

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Report refusals: 403 in modal, staff-only accounts allowed | S | — | DONE |

### Phase 1 — Report refusals

**Goal.** 403 shows its message in the modal; moderator-only accounts can report. **Reads.** §1.4, §2.

**Deliverables.**
- `app/Domains/Moderation/Private/routes.php`: widen role list on the report group.
- `app/Domains/Moderation/Private/Resources/js/moderation.js`: 401-only redirect, 403 message.
- `app/Domains/Moderation/Private/Resources/js/moderation.test.js`: new.
- `app/Domains/Moderation/Tests/Feature/SubmitModerationReportTest.php` (or a new `ReportRoleGateTest.php`): role cases.

**Tests.**
- Pest: a user with only `moderator` can GET the form and POST a report (200); only `admin` likewise; a user with no role is still refused; guest still redirected/401.
- Vitest: submit with 403 `{success:false,message}` sets `errorMessage`, no redirect; submit with 401 redirects to `/login`; form load 401 redirects, 403 does not.

**Acceptance.**
- ✅ Tests above pass, written failing first.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Report modal | A refused submit (guard 403) shows the message in the modal, no redirect | |
| Comment list as a moderator-only account | « Signaler » submits successfully | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Allow moderator/admin/tech-admin through the route gate rather than hiding the button (the comment list and annotation hint already show it to them; guard accepts them). | REFINE | Yes |
| 2 | On form-load 403, a generic alert suffices (role-gate 403 body is not the guard JSON). | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE (2026-11-01): route gate widened to staff roles, JS shows 403 message / 401-only redirect. Tests: `ReportRoleGateTest.php`, `moderation.test.js`.
