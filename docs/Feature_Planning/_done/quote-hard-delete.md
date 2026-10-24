# Quote — reader quote deletion must be a hard delete

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-10-24 (built on branch `fix/quote-hard-delete`, pending
merge) · **Domain(s):** `Quote`

## What it does

Bug fix. A reader deleting one of their own quotes used to soft-delete it, so
`RestoreOnUserReactivated` brought it back after a deactivation/reactivation
cycle. `QuoteService::delete()` now calls `forceDelete()`. A one-off migration
purges every row that was already soft-deleted. No UI change, no API change, no
new event.

## Key behaviour

- **Reader deletion:** permanent (`forceDelete()`), after the unchanged
  `QuotePolicy::canEditOrDelete()` check (403 for non-owners). Uses
  `findOrFail` with the default scope, so a quote that is already hidden returns a 404.
- **Deactivation:** still soft-deletes (`SoftDeleteOnUserDeactivated`, unchanged).
- **Reactivation:** still `withTrashed()->restore()` on every soft-deleted row of
  the user (`RestoreOnUserReactivated`, unchanged). It is now correct because
  of the invariant below, not because of new logic.
- **User deletion:** still a raw `DB::table('quotes')` hard delete (unchanged).
- **Invariant:** `deleted_at IS NOT NULL` ⇔ owner currently deactivated. Never
  soft-delete a quote for any other reason, or reactivation will restore it.
  This is written into `app/Domains/Quote/README.md` and `AGENTS.md`.
- `Quote` keeps `SoftDeletes`. There is no marker column. Deactivation is the
  only thing that writes `deleted_at`.
- Calendar Quote Contest entries snapshot the quote and never read `quote_id`
  back, so a hard-deleted source quote does not affect them.

## Deployment precondition

`2026_10_24_000000_purge_soft_deleted_quotes` runs
`DELETE FROM quotes WHERE deleted_at IS NOT NULL` with no scoping. It is safe
only if **production has no deactivated user** when it runs, because their
hidden quotes would be purged as well. The user stated this is true today
(DECISIONS #3). **Check it again before deploying.** If a deactivated user
exists, scope the purge to exclude their `user_id` first. `down()` is a no-op
and there is no backup table.

## Where the code lives

| Concern | Path |
|---------|------|
| Service | `app/Domains/Quote/Private/Services/QuoteService.php` (`delete()`) |
| Lifecycle listeners (unchanged) | `app/Domains/Quote/Private/Listeners/` |
| Purge migration | `app/Domains/Quote/Database/Migrations/2026_10_24_000000_purge_soft_deleted_quotes.php` |
| Tests | `Quote/Tests/Feature/QuoteControllerTest.php` (owner delete → `assertDatabaseMissing`), `QuoteLifecycleTest.php` (reader-deleted quote not restored on reactivation), `PurgeSoftDeletedQuotesMigrationTest.php` |
| Domain docs | `app/Domains/Quote/README.md`, `app/Domains/Quote/AGENTS.md` (Invariants) |

The migration test runs `(require base_path('…migration.php'))->up()`. This is
the project's first migration test, and the pattern can be reused.

## Decisions worth remembering

- **No marker column** (A1). With hard deletion, `deleted_at` is no longer
  ambiguous, so the reactivation listener did not need to change.
- **Purge the legacy rows** (A2). The model works without the purge, but
  without it every pre-fix reader deletion would come back at the next
  reactivation, and the planned quote statistics would count those rows as
  "deactivated".
- **`down()` is a no-op** (A3). Purged rows cannot be recreated.

## Plan vs code

The code matches the single-phase plan. There were no deviations and no fix commits.

## Not done

- Non-goals: emitting a quote-deletion domain event (phase 2 of
  `admin-statistics-quotes/`), any UI change (no undo, no new confirmation),
  and moderator access to the admin statistics page.
- Nothing was cut. No new backlog rows.
- e2e: no spec was added. VERIFY used a throwaway `run-app` flow on the 5 UI
  surfaces (chapter panel, profile Citations tab and its empty state, author
  badge count, mobile 375 px), and all passed. The deactivated-user state has
  no UI and is covered by feature tests only.
- **Dependent task:** `admin-statistics-quotes/` (backlog, WIP:PLAN) needs this
  fix. Its BUILD can start once this branch is merged, because its quote
  totals rely on a reader-deleted quote being gone for good.
