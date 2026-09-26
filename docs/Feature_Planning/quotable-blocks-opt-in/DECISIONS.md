# Quotable blocks opt in — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-26 | REFINE | How is quotability modelled? | Two levels: the chapter content area is the quote zone (hook); each area inside declares quotable true/false, default false. | — |
| 2 | 2026-09-26 | REFINE | Stored marker + backfill, or legacy fallback? | Neither: the quotable flag is injected at display time, not read from the stored record. No backfill. | — |
| 3 | 2026-09-26 | REFINE | Quotable fixed by block type or per-block author toggle? | Fixed by block type: text quotable, image (+caption) and future chapter-choice not. | — |
| 4 | 2026-09-26 | REFINE | Existing quotes whose text/prefix/suffix spans a caption? | Accept possible staleness; no audit command. | — |
| 5 | 2026-09-26 | REFINE | Selection touching a non-quotable area? | Hide « Citer » in the selection toolbar; other toolbar actions unaffected. | — |
| 6 | 2026-09-26 | REFINE | Align cross-block rule with #5? | No — cross-block keeps its existing error in the mini-form. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | No role/visibility change: who can quote or see highlights/heat/summary is unchanged. | REFINE (replay confirmed) | yes |
| 2 | No new French wording (hidden button needs no message). | REFINE (replay confirmed) | yes |
| 3 | Same behaviour on mobile touch selection. | REFINE (replay confirmed) | yes |
| 4 | Injection mechanism (block class vs `content_blocks`) left to DESIGN. | REFINE (replay confirmed) | yes |
| 5 | No notifications, events, settings, stats, search or migrations. | REFINE (replay confirmed) | yes |
