# Quote — reader quote deletion must be a hard delete — architecture

> DESIGN output (auto mode). Tradeoffs were decided without the user and are
> recorded as assumptions in [`DECISIONS.md`](./DECISIONS.md).

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Quote domain only. No other domain changes.

### 1.1 Changes in other domains

None. Calendar's Quote Contest snapshots quotes at submission and never reads
`quote_id` back (see the docblock of its `create_calendar_quote_contest_entries_table`
migration), so a hard-deleted source quote does not affect it.

## 2. Data model

### 2.1 Tables

`quotes` unchanged. `deleted_at` stays: deactivation still needs it.

### 2.2 Model

`Quote` keeps `SoftDeletes`. The trait is required by
`SoftDeleteOnUserDeactivated` (`->delete()` soft-deletes) and
`RestoreOnUserReactivated` (`withTrashed()->restore()`).

### 2.3 Lifecycle rules

There is a single soft-delete column. Before the fix, a reader deletion and a
deactivation both wrote `deleted_at`, and the two were indistinguishable — which
is the bug. After the fix, **a soft-deleted row can only come from a
deactivation**, so the existing `RestoreOnUserReactivated`
(`withTrashed()->where('user_id', …)->restore()`) restores exactly the right
set without modification. No new column, no marker.

Invariant to document: *`deleted_at IS NOT NULL` ⇔ owner currently
deactivated.*

Legacy data: rows already soft-deleted by readers before the fix would break
that invariant (resurrected at the owner's next reactivation, and counted as
"deactivated" by the future quote statistics). A one-off data migration purges
them: `DELETE FROM quotes WHERE deleted_at IS NOT NULL`. This is safe only
because there are no deactivated users in production (user guidance), so every
soft-deleted row today is a reader deletion. `down()` is a deliberate no-op —
purged rows cannot be recreated.

## 3. PHP architecture

### 3.1 Public API

Unchanged. `QuotePublicApi` exposes no delete method; read methods already
exclude trashed rows via the default scope.

### 3.2 Services

`QuoteService::delete(int $quoteId, int $userId): void` — same signature, same
authorisation; replaces `$quote->delete()` with `$quote->forceDelete()`.
`findOrFail` keeps the default scope, so a deactivated user's hidden quote is
still a 404 (unchanged; the user cannot be logged in anyway).

### 3.3 Policy / authorization

Unchanged (`QuotePolicy::canEditOrDelete`, owner-only).

### 3.4 Events and listeners

No event emitted (unchanged; `admin-statistics-quotes/` phase 2 will add
`ChapterPassageQuoteDeleted` after this hard delete). The three lifecycle
listeners are unchanged.

### 3.5 Routes, controllers, form requests

Unchanged (`DELETE /quotes/{id}` → 204).

## 4. Frontend architecture

None. The client already removes the quote from its store on 204.

## 5. Deptrac

No new edge.

## 6. Testing strategy

Feature tests only:
- controller: owner delete leaves no row (`assertDatabaseMissing`), replacing
  the current `assertSoftDeleted`;
- lifecycle: reader deletes one quote, owner deactivated then reactivated → the
  deleted quote is still missing, the others are live again;
- migration: requiring the migration file and running `up()` removes
  soft-deleted rows and keeps live ones.

Tests that call `$quote->delete()` directly (`ChapterAggregateTest`,
`QuotePublicApiOwnerReadsTest`) model a deactivation-hidden row and stay valid.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How to tell reader-deleted from deactivation-hidden rows | (a) hard delete on reader action, (b) add a `hidden_by_deactivation` flag / separate column, (c) drop `SoftDeletes` and move hidden rows elsewhere | (a) | Once reader deletes never soft-delete, the existing column already means "hidden by deactivation". Minimal change. (A1) |
| 2 | Legacy soft-deleted rows | (a) purge migration, (b) leave them | (a) | Leaving them keeps the bug alive for every past deletion and skews the planned statistics; prod has no deactivated users, so the purge is lossless for live data. (A2) |
| 3 | Purge reversibility | (a) no-op `down()`, (b) backup table | (a) | Rows are user-deleted content; keeping a copy contradicts the intent. (A3) |

## 8. File layout

```
app/Domains/Quote/Database/Migrations/
└── 2026_10_24_000000_purge_soft_deleted_quotes.php   (new, data-only)
app/Domains/Quote/Tests/Feature/
└── PurgeSoftDeletedQuotesMigrationTest.php            (new)
```

## 9. Risks acknowledged

- If a user *is* deactivated in production when the migration runs, their
  hidden quotes are purged too. Revisit (scope the purge) if production data
  contradicts the "no deactivated users" guidance before deployment.
- Any future code path that soft-deletes a quote for another reason would
  re-introduce the ambiguity; the invariant is written into the domain docs.
