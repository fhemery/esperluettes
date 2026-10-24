# Quote — reader quote deletion must be a hard delete — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Quote — hard delete on reader deletion + legacy purge migration | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

One phase: the service change, the migration and the doc update are a few
lines each and only make sense together (the migration restores the invariant
the service change establishes). No refactor phase, no checkpoint row: no
shared code is reshaped and no page changes.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

### Facts verified during PLAN (HEAD `6bf9a786`)

- `Quote` model uses `SoftDeletes`; `QuoteService::delete()` calls
  `$quote->delete()` (soft) after `QuotePolicy::canEditOrDelete()`.
- `SoftDeleteOnUserDeactivated`: `Quote::where('user_id', …)->delete()`.
  `RestoreOnUserReactivated`: `Quote::withTrashed()->where('user_id', …)->restore()`.
  `DeleteQuotesOnUserDeleted`: raw `DB::table('quotes')->where(...)->delete()`.
  All three stay as they are.
- The only `withTrashed()` in the codebase for quotes is in
  `RestoreOnUserReactivated`; nothing reads reader-deleted rows back.
- Calendar Quote Contest entries snapshot the quote; no read path dereferences
  `quote_id`.
- No migration test exists elsewhere in the project; requiring the migration
  file (`(require base_path('…'))->up()`) is the simplest way to test it.
- No deptrac change: everything stays inside `QuotePrivate` / `QuoteTests`.

---

## Phase 1 — Quote — hard delete on reader deletion + legacy purge migration

**Goal.** A reader's own deletion removes the row permanently, and rows
soft-deleted that way before the fix are purged, so that a soft-deleted quote
always means "owner deactivated" and reactivation restores only those.

Architecture: §2.3, §3.2, §6.

**Deliverables.**
- `app/Domains/Quote/Private/Services/QuoteService.php` — in `delete()`, replace
  `$quote->delete();` with `$quote->forceDelete();`. Nothing else changes
  (keep `findOrFail` with the default scope and the policy check).
- `app/Domains/Quote/Database/Migrations/2026_10_24_000000_purge_soft_deleted_quotes.php`
  — anonymous migration class. `up()`:
  `DB::table('quotes')->whereNotNull('deleted_at')->delete();`
  with a docblock saying: before this fix reader deletions were soft deletes;
  production has no deactivated user at deployment time, so every soft-deleted
  row is a reader deletion and is purged. `down()`: no-op with a comment that
  purged rows cannot be recreated (the project requires a `down()` method).
- `app/Domains/Quote/README.md` and `app/Domains/Quote/AGENTS.md` — state that
  a reader's deletion is a hard delete, and add the invariant
  « `deleted_at` set ⇔ owner currently deactivated; never soft-delete a quote
  for any other reason, or reactivation will restore it ». In AGENTS.md this
  goes in "Invariants"; also adjust the `Auth::UserReactivated` line to say it
  restores the rows hidden by the deactivation. No link to `docs/Feature_Planning`.

**Tests.**
- `app/Domains/Quote/Tests/Feature/QuoteControllerTest.php` — in the existing
  `it('owner can delete a quote')` (line ~261), replace
  `assertSoftDeleted('quotes', …)` with
  `assertDatabaseMissing('quotes', ['id' => $quote->id])` (raw query, ignores
  the soft-delete scope). Write this change first and watch it fail.
- `app/Domains/Quote/Tests/Feature/QuoteLifecycleTest.php`, `describe('user lifecycle cascades')`:
  `it('does not restore a quote the reader deleted when the owner is reactivated')` —
  reader has quotes A and B; reader `deleteJson('/quotes/'.A)` → 204;
  `event(new UserDeactivated($reader->id))`; `event(new UserReactivated($reader->id))`;
  assert A missing from `quotes`, B present with `deleted_at` null.
- `app/Domains/Quote/Tests/Feature/PurgeSoftDeletedQuotesMigrationTest.php` (new):
  `it('purges soft-deleted quotes and keeps live ones')` — create a live quote
  and a quote then `->delete()` it (soft); run
  `(require base_path('app/Domains/Quote/Database/Migrations/2026_10_24_000000_purge_soft_deleted_quotes.php'))->up();`
  assert the soft-deleted id is missing and the live one is present. Uses the
  domain's `createQuote()` / `alice()` / `bob()` / `publicStory()` /
  `createPublishedChapter()` helpers like the other tests in the folder.
- Existing tests that call `$quote->delete()` directly
  (`ChapterAggregateTest`, `QuotePublicApiOwnerReadsTest`) and the existing
  deactivation/reactivation/deletion tests stay unchanged and green.

**Acceptance.**
- ✅ `DELETE /quotes/{id}` by the owner returns 204 and the row no longer exists,
  `withTrashed()` included.
- ✅ A non-owner's `DELETE` still returns 403 and the row stays live.
- ✅ Deactivate → reactivate restores every quote hidden by the deactivation and
  none the reader deleted beforehand.
- ✅ User deletion still hard-deletes all the user's rows (existing test green).
- ✅ The migration removes every soft-deleted quote and no live one; `down()`
  exists and does nothing.
- ✅ Quote README/AGENTS state the invariant and contain no `docs/Feature_Planning` link.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter page, reader quote panel (`user-confirmed`) | Delete a quote: it disappears from the panel and the highlight goes; reload — still gone. | |
| Profile « Citations » tab, owner | Delete a quote from the list: it disappears, total decrements; reload — still gone. | |
| Profile « Citations » tab, empty state | Delete the last quote: empty state shows. | |
| Chapter page, author heat / badge | After a reader deletes their quote, the author's count and heat no longer include it. | |
| Mobile (≈375 px), chapter panel | Delete flow still usable, no layout change. | |

Deactivated-user state is covered by feature tests only (no UI surface: a
deactivated user cannot log in, and their quotes are already hidden from others).

## Open items

None blocking. Pre-deployment check (outside the build): confirm production
still has no deactivated user when the migration runs (architecture §9) —
otherwise their hidden quotes would be purged too.
