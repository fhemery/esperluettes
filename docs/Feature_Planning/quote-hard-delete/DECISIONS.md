# Quote — reader quote deletion must be a hard delete — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-24 | REFINE | Reader deletion semantics | Hard delete (`00-request.md`). | — |
| 2 | 2026-10-24 | REFINE | Deactivation / reactivation | Keep soft-deleting on deactivation; reactivation restores only those quotes, never reader-deleted ones (`00-request.md`). | — |
| 3 | 2026-10-24 | REFINE | Production data | There are no deactivated users in production; currently soft-deleted quote rows may simply be hard-deleted (user guidance relayed by the orchestrator). | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | No marker column is needed. Deactivation and reader deletion share `deleted_at`; once reader deletion is a hard delete, a soft-deleted row can only come from a deactivation, so `RestoreOnUserReactivated` stays untouched and is naturally correct. `SoftDeletes` stays on the model. | DESIGN | Yes |
| A2 | A one-off data migration *is* needed: `DELETE FROM quotes WHERE deleted_at IS NOT NULL`. The model does not require it, but without it every pre-fix reader deletion would be resurrected at a future reactivation and counted as "deactivated" by the planned quote statistics. Lossless given #3. | DESIGN | Before deployment only |
| A3 | The purge migration's `down()` is a no-op (purged content cannot be recreated); no backup table. | DESIGN | Yes |
| A4 | Single phase: service change + migration + domain-doc invariant ship together. No checkpoint (no shared code or page reshaped). | PLAN | Yes |
