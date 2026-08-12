# Domain docs gate check — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-12 | REFINE | What must every domain have? | `README.md`, `AGENTS.md`, `CLAUDE.md` at domain root | — |
| 2 | 2026-08-12 | REFINE | Registry requirement? | Every domain must have a row in root `AGENTS.md` Domain Registry | — |
| 3 | 2026-08-12 | REFINE | Where does the check run? | Inside the gate docs step (`pnpm run gate` → docs) | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Domains = immediate subdirectories of `app/Domains/` | REFINE | Yes — could instead take the Domain Registry as the sole source of truth |
| A2 | `CLAUDE.md` must be exactly `@AGENTS.md` + trailing newline (not mere presence) | REFINE | Yes — presence-only is weaker; prior WRAP preferred the contract |
| A3 | Registry check is bidirectional (disk↔registry) | REFINE | Yes — request only required disk→registry |
| A4 | Document and register `Follow` in this task so the new checks pass; no permanent exception | REFINE | Yes — could exclude Follow temporarily, but that defeats the filed purpose |
| A5 | Enforce in `scripts/check-docs.js` only; no PHPUnit "file exists" test | REFINE | Yes — prior WRAP called PHPUnit for this test theatre |
| A6 | Error messages stay English, matching existing `check-docs.js` style | REFINE | Yes |
| A7 | Extend `scripts/check-docs.js` rather than adding a new gate step | DESIGN | Yes |
| A8 | Prove helpers with a small Node/Vitest suite + gate acceptance; VERIFY skipped (no UI) | DESIGN | Yes |
