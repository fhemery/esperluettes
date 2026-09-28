# Upgrade pnpm to 12 — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Target **12.6.0** (npm `latest`, published 2026-09-22), not 12.8.1 (`next-12`, published today): it respects the repo's 48 h release-age rule | REFINE | yes — bump the pin |
| A2 | Migrate `auditConfig.ignoreGhsas` → `audit.ignore` (same GHSA, same comment) rather than rely on the legacy key surviving 12 | REFINE | yes |
| A3 | Update ADR 0001 **in place** (request says "update the ADR"), not a superseding ADR 0002 | REFINE | yes |
| A4 | Leave `docker/8.4/Dockerfile` unpinned (`npm install -g pnpm`): it already gets 12 from `latest`, and pnpm self-switches to the `packageManager` pin | REFINE | yes — pin it later |
| A5 | Lockfile may take a format-only rewrite under 12; no dependency version may move | REFINE | yes |
| A7 | T1: CI keeps `npm install -g "<packageManager>"`; switch to `pnpm/action-setup` only if that yields a broken pnpm 12 | DESIGN | yes |
| A8 | T2: CI pnpm store cache key unchanged (store path is layout-versioned) | DESIGN | yes |
| A9 | T3: no `devEngines`/`engines` enforcement; local devs rely on pnpm self-switching + updated setup docs | DESIGN | yes |
| A10 | Also update the `auditConfig.ignoreGhsas` mention in `.agents/skills/upgrade-dependencies/SKILL.md` to `audit.ignore` — it is a live instruction, not a historical record, and would otherwise point at a key that no longer exists | PLAN | yes |
| A11 | Single phase: the pin and the audit-key rename cannot land separately (pnpm 12 rejects unknown keys), and the doc edits are not verifiable apart from the pin. No automated test is added; operational checks replace it | PLAN | yes |
| A6 | Historical records (`_done/`, other requests, Done list) and the illustrative "pnpm 11" in `write-adr` skill stay untouched | REFINE | yes |
