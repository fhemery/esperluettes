# Admin menus E2E — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-08 | REFINE | What does the feature cover? | E2E sidebar inventory for moderator / admin / tech-admin; explicit mapping; core suite. From `00-request.md`. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Assert link inventory only — do not click through every admin page. Matches e2e README philosophy (don't duplicate PHP auth tests). | REFINE | Yes — expand later if smoke depth is wanted |
| A2 | Add a `tech-admin` E2E account (seeder + Playwright fixtures); none exists today. | REFINE | No — required to cover the third role |
| A3 | Expected sets freeze current `registerPage` permissions (see research table in session); mapping lives next to the core spec. | REFINE | Yes — if product wants a different inventory, change mapping + registrations together |
| A4 | Deduplicate mobile+desktop duplicate sidebar links by href/label. | REFINE | Yes |
| A5 | Out of scope: Filament parity, non-staff access, README refresh, page content asserts. | REFINE | Yes |
| A6 | Identify sidebar links via new `data-nav-key` (registry page key); not href or FR labels. | DESIGN | Yes — switch to href if markup change is refused |
| A7 | Mapping lives only in TS under `e2e/` (no PHP inventory twin). | DESIGN | Yes |
| A8 | Assert set equality of nav keys only; no click-through. | DESIGN | Yes (same as A1) |
| A9 | Register maintenance page with literal key `maintenance` (was `__('…key')`, which broke under testing locale `zz`). | BUILD | Yes |
