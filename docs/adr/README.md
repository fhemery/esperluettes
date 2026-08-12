# Architecture Decision Records

Durable, numbered decisions that outlive any single feature task. Write new
ones with the **`write-adr`** skill (`.agents/skills/write-adr/`).

**Hard rule:** ADRs must be self-contained. Do not link to disposable loop task
folders (in-flight or archived WRAP records under `docs/`). Fold durable
context into the ADR body. `pnpm run gate` enforces it.

| # | Title | Status |
|---|-------|--------|
| [0001](0001-use-pnpm.md) | Use pnpm as the sole Node package manager | Accepted |
