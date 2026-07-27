# Backlog

The entry point of the loop. `/next-task` picks the first `TODO`;
`/continue-task` resumes the first `WIP:*`. Protocol:
[`.agents/loop/README.md`](../../.agents/loop/README.md).

This file is the loop's mutable state and lives next to the task folders it
points at; `.agents/loop/` holds only the static protocol and templates.

Statuses: `TODO` · `WIP:<STEP>` · `BLOCKED:<reason>` · `DONE`
Steps: `REFINE DESIGN PLAN BUILD VERIFY WRAP`
Modes: `interactive` (stop at each step) · `auto` (run through, report at the end)

Order matters: the top-most `TODO` is the next task. Reorder rows freely.

| # | Task | Folder | Mode | Status |
|---|------|--------|------|--------|
| 1 | Quotes — in-chapter author view (vNext) | `quotes-author-view/` | interactive | TODO |
| 2 | Quotes — moderation of quotes and notes | `quotes-moderation/` | interactive | TODO |
| 3 | Chapter annotations | `annotations/` | interactive | BLOCKED:migrate `Chapter_Annotations*.md` into the loop format once task #1 has validated it |

## Done

| Task | Docs | Wrapped |
|------|------|---------|
| Quotes v1 | `Quotes.md`, `Quotes_Architecture.md`, `Quotes_Implementation_Plan.md` | 2026-07-27 (pre-loop; docs not yet compacted into a folder) |
