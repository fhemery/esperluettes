# Calendar: dedicated time field for activity dates — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-24 | REFINE | How is the time entered? | Date + time native inputs side by side | — |
| 2 | 2026-10-24 | REFINE | Where does the split apply? | Change the shared component everywhere (Calendar, Story chapters, Auth codes, Events log) | — |
| 3 | 2026-10-24 | REFINE | Date chosen, time empty? | Prefill 00:00 | — |
| 4 | 2026-10-24 | REFINE | Assumptions (clear date clears time; Quote Contest mirrors split too; no server/storage change; free minutes) | Confirmed in replay | — |
| 5 | 2026-10-24 | DESIGN | Only Calendar uses the shared component; Story/Auth/Events use raw inputs. How far does the split go? | Calendar + Quote Contest + Story chapter `publish_at`; Auth codes and Events log stay as they are | 2 (partly) |
| 6 | 2026-10-24 | DESIGN | How does the split component submit its value? | Hidden combined input under the original name, two unnamed visible inputs | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Quote Contest's read-only mirror becomes a `mirrorOf` mode of the component | DESIGN | Yes |
