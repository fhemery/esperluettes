# Admin pages — flash message shown twice

**Status:** DONE — 2026-09-28 · **Domain(s):** `Administration` (owner), with one-line
view edits in Auth, Calendar, FAQ, Moderation, Events and Config · **Mode:** auto

## What it does

Admin pages showed every flash message (`success` / `error` / `status`) and the
« Oups » validation-error box twice. Both `<x-admin::layout>` and 23 page views
rendered `<x-shared::flash-block />`. The fix makes the admin layout the only
place that renders it: the 23 page-level copies were deleted. A guard test stops
anyone from adding one back. The change is Blade-only; no PHP production code
changed.

## Key behaviour

- **Owner:** `Administration/Private/Resources/views/layouts/layout.blade.php:12`
  is the only place on admin pages that renders the flash block.
- **Rule:** no view under `app/Domains/*/Private/Resources/views/pages/admin/**`
  may contain `x-shared::flash-block`. `AdminPagesFlashBlockGuardTest` enforces
  this, and the rule is also recorded in the Administration `AGENTS.md`.
- **Roles:** unchanged. The fix was checked as admin, tech-admin and moderator.
- **Unchanged:** the component, which is `position: fixed`, takes no props and
  auto-hides after 10 s; the public layout `Shared/…/layouts/app.blade.php`; and
  the inline `session('status')` in the Auth login and password forms.
- **Counter-intuitive:** the guard only scans the `pages/admin` path convention.
  An admin view stored anywhere else is not checked (accepted risk).

## Where the code lives

| Concern | Path |
|---------|------|
| Flash owner | `app/Domains/Administration/Private/Resources/views/layouts/layout.blade.php` |
| Component (untouched) | `app/Domains/Shared/Resources/views/components/flash-block.blade.php` |
| Views cleaned (23) | `app/Domains/{Auth,Calendar,FAQ,Moderation,Events,Config}/Private/Resources/views/pages/admin/**` |
| Guard test (file scan, no DB, asserts it scanned more than 0 files) | `app/Domains/Administration/Tests/Feature/AdminPagesFlashBlockGuardTest.php` |
| Render test (message count is 1, for both success and error bag) | `app/Domains/Config/Tests/Feature/Admin/FeatureToggleFlashTest.php` |
| e2e page object | `e2e/pages/AdminActivityFormPage.ts` — `flash()` no longer calls `.first()` |

## Extension points used

None.

## Decisions worth remembering

All of these were **assumptions made in auto mode**, not user decisions. Each is
reversible:

1. The layout keeps the block and the pages lose it, rather than the other way
   round. The layout already covers the ~40 admin pages that never had their
   own copy.
2. No admin page needs its own flash placement, because the component is fixed
   and takes no props.
3. Regression coverage is one Config render test plus one file-scan guard in
   Administration. The guard can be dropped if it is not wanted.

## Plan vs code

- The code matches the plan: 23 deletions and two tests, done in one phase.
- One change was not in the plan. At VERIFY, `AdminActivityFormPage.flash()`
  dropped the `.first()` that had hidden the duplicate. As a result, the core
  spec `e2e/tests/core/confirm-modal.spec.ts` now fails through Playwright
  strict mode if a second copy returns. This was confirmed by adding the block
  back and watching the spec fail.

## Not done

- **Non-goals:** no redesign of the flash block (position, timing), and no
  change to how non-admin pages handle flashes.
- **Cut mid-build:** nothing.
- **Visual QA gap:** Moderation › reports show was not checked in the browser,
  because the e2e world seeds no report. The guard test covers it.
- **e2e:** no feature spec was created, so nothing needed retiring.
- **Open questions / backlog:** none.
