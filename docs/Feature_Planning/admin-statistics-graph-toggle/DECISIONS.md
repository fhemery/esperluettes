# Admin statistics — cumulative / non-cumulative graph toggle — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-27 | REFINE | Unit of a point in non-cumulative mode | Calendar weeks (Mon→Sun); finer range/grain toggle later | — |
| 2 | 2026-09-27 | REFINE | Toggle placement | One switch for the whole page | — |
| 3 | 2026-09-27 | REFINE | Remember the mode? | Per browser; default cumulative | — |
| 4 | 2026-09-27 | REFINE | Current partial week | Shown, visually marked, labelled "semaine en cours" | — |
| 5 | 2026-09-27 | REFINE | Chart form in weekly mode | Bars | — |
| 6 | 2026-09-27 | REFINE | Roots/replies chart in weekly mode | Stacked bars | — |
| 7 | 2026-09-27 | REFINE | Negative weekly values | Net value, negatives allowed | — |
| 8 | 2026-09-27 | DESIGN | Where are weekly values computed / how does the switch redraw? | Server aggregates daily deltas into weeks; both datasets embedded in the page; in-place switch, no reload | — |
| 9 | 2026-09-27 | DESIGN | Where does the switch component live? | Generic `x-shared::segmented-control` in Shared (reused by the future range selector) | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Labels "Cumulé" / "Par semaine"; tooltip "Semaine du {date} : {valeur}" (confirmed on replay) | REFINE | yes |
| 2 | Week starts Monday, app timezone (confirmed on replay) | REFINE | yes |
| 3 | Number tiles unaffected (confirmed on replay) | REFINE | yes |
| 4 | Hidden-tab graphs switch too (confirmed on replay) | REFINE | yes |
| 5 | Switch is a keyboard-operable button group with selected state (confirmed on replay) | REFINE | yes |
| 6 | No specific mobile work (confirmed on replay) | REFINE | yes |
