# Moderation — report refusals: 403 handling and moderator-only accounts

**Status:** DONE — 2026-11-01 · **Domain(s):** `Moderation`

## What it does

The report modal no longer redirects to `/login` on a 403: it stays open and
shows the response `message` (generic error if absent). Only 401 redirects.
The report routes also accept accounts holding only `moderator` / `admin` /
`tech-admin`, so staff-only accounts can submit reports like anyone else.

## Key behaviour

- `GET moderation/report-form/...`: 401 -> `/login`; 403 -> generic alert, no redirect.
- `POST moderation/report`: 401 -> `/login`; 403 -> `data.message` in the modal's error area, modal stays open, nothing stored; no message in body -> generic `moderation::report.error`.
- Route role gate: `user`, `user-confirmed`, `moderator`, `admin`, `tech-admin` (auth + `compliant` unchanged).
- Who may report what per topic is unchanged (topic guards, `can_report` hint).
- A role-middleware refusal is a 302 redirect, not a 403 (see limits).

## Where the code lives

| Concern | Path |
|---------|------|
| Routes (role gate) | `app/Domains/Moderation/Private/routes.php` |
| JS | `app/Domains/Moderation/Private/Resources/js/moderation.js` |
| Tests | `app/Domains/Moderation/Tests/Feature/ReportRoleGateTest.php`, `app/Domains/Moderation/Private/Resources/js/moderation.test.js` (vitest, happy-dom, mocked `fetch`/`location`) |

## Extension points used

None. Moderation only; no table, event or deptrac edge.

## Decisions worth remembering

- Staff roles were let through the route gate rather than hiding the button: comment list and annotation hint already show « Signaler » to them and the guards accept them.
- Form-load 403 is a generic alert: a role-gate refusal body is not the guard JSON.

## Verified limits (code is the truth)

1. `ReportController::store()` returns the same generic message for every authorization failure, so a guard's specific message never reaches the user. The JS displays `data.message` if present, but nothing sends a specific one. Backlog: `moderation-report-guard-message/`.
2. The comment-list « Signaler » is unreachable for staff-only accounts (the comments page refuses non-members: « Seuls les membres peuvent accéder aux commentaires »). The chapter-page « Signaler » works for them. The plan's claim that the comment list exposes the button to them does not hold.
3. A role-less account gets a 302 to `/dashboard` from the role middleware; `fetch` follows it, `response.redirected` is true, the JS sets `location.href = response.url`, and `/dashboard` loops for such an account (spinner, then generic error). Every real account has a `user*` or staff role, so nobody hits it; the redirect branch pre-dates this change.

## Not done

- Non-goals: changing who may report what on any topic; hiding the button for moderators; changing the `can_report` hint.
- Cut mid-build: nothing. VERIFY: PASS (throw-away Playwright spec, deleted, not promoted; existing `chapter-annotation-report` core spec 3/3).
- Pushed back to the backlog: `moderation-report-guard-message/` (limit 1).
