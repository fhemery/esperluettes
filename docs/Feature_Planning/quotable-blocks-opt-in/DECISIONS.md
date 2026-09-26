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
| 7 | 2026-09-26 | DESIGN | Where is quotability decided at display time? | Client-side from the stored block class, hardcoded: only `.ce-block--text` is quotable. No `data-quotable` attribute, no server rewrite, no re-render. | — |
| 8 | 2026-09-26 | DESIGN | Where does the quotable selector live? | In Quote, passed as a filter to the generic Shared canonical-text function — annotations may later include images. | — |
| 9 | 2026-09-26 | DESIGN | How does Simple mode fit the rule? | Wrap Simple content in `div.ce-block.ce-block--text` at display time (user's suggestion). | — |
| 10 | 2026-09-26 | DESIGN | How is « Citer » hidden? | Declarative `data-requires-selection-within` on toolbar actions, evaluated by Comment's toolbar; toolbar hidden when no action applies. | — |

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
