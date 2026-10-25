# Profile — deactivated user's profile page — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 1 /5 · **Mode:** auto

## 0. Handoff

### REFINE → BUILD (2026-10-25)

- Probe done: `/profile/<slug>` of a deactivated user already answers 404 (no 500). The probe was kept as the regression test (see §3), so BUILD only has to confirm it and run the gate.
- No blocking question asked. Likeliest assumption to be reversed: 404 rather than a "compte désactivé" page (§4).

## 1. Functional spec

### 1.1 Overview

Opening `/profile/<slug>` for a deactivated user must not crash. Observed: it returns a 404 for everyone (guest, confirmed user, admin), on the default page and on a named tab. The 500 reported earlier no longer occurs.

**Root cause (of the old report).** Deactivating a user soft-deletes their profile (`UserDeactivatedSoftDeleteTest`), so route-model binding on `Profile` finds nothing and raises a 404. That 404 used to crash while rendering the error page (undefined `$theme`), turning it into a 500; `fix-404-page-theme-500` removed that crash. Nothing else in the profile code path is reached.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Utilisateur désactivé | User with `is_active = false`; profile soft-deleted, restored on reactivation |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Everyone (guest, `user`, `user-confirmed`, moderator, admin) | Standard 404 page | Nothing |

### 1.4 Functional requirements

1. Any request to `/profile/<slug>` or `/profile/<slug>/<tab>` for a deactivated user returns the standard 404 page.

### 1.5 Lifecycle

Reactivation restores the profile (`UserReactivatedRestoreTest`), and the page works again.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Same for all roles |
| Visibility / privacy | Deactivated profile is hidden, consistent with `ProfilePublicApi::getPublicProfile` returning null |
| Settings, Notifications, Domain events, Statistics, Moderation, Media, Search, Mobile, Accessibility | N/A — no behaviour change |
| Lifecycle / cascade | Already handled by soft-delete |
| i18n | Standard 404 page, already translated |

### 1.7 Out of scope

- A dedicated "compte désactivé" page.
- Moderator/admin access to deactivated profiles.
- Other pages listing deactivated users (stories co-author label was handled elsewhere).

### 1.8 Open questions

None blocking.

## 2. Architecture

### 2.1 Domain placement

Profile, tests only. No production code change, no new deptrac edge.

### 2.6 Testing strategy

One Pest feature test in `app/Domains/Profile/Tests/Feature/UserDeactivatedSoftDeleteTest.php`: deactivate a user, assert 404 for guest, confirmed user and admin, on the default page and the `stories` tab.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Regression test for deactivated profile page | S | — | TODO |

### Phase 1 — Regression test

**Goal.** Lock in the 404. **Reads.** §2.6.

**Deliverables.** The test above, already written during REFINE and passing; BUILD verifies it and runs the gate.

**Tests.** `Opening the profile page of a deactivated user › answers 404, never 500, ...`.

**Acceptance.**
- ✅ Test green.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| `/profile/<slug>` of a deactivated user | Themed 404 page, no 500 | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | A deactivated profile shows the standard 404, not a "compte désactivé" page. This is the existing behaviour (soft-deleted profile) and matches the public API hiding it. | REFINE | Yes |
| 2 | Moderators and admins get the 404 too. | REFINE | Yes |

## 5. Build & verify log

_Not written yet._
