# Multi-edit — chapter switch block — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-26 | REFINE | Target unpublished or deleted — what does a reader see? | Hidden for readers; authors/co-authors see it marked « non publié » | — |
| 2 | 2026-09-26 | REFINE | Button text? | Custom label, fallback to target chapter title when empty | — |
| 3 | 2026-09-26 | REFINE | One button per block or a group? | Group of 1..N choices; hidden choices drop out, all-hidden block disappears | — |
| 4 | 2026-09-26 | REFINE | Target hard-deleted — author's editor view? | Flagged « Chapitre supprimé », kept until fixed, save allowed | — |
| 5 | 2026-09-26 | REFINE | Touch linear prev/next navigation? | No, unchanged — out of scope | — |
| 6 | 2026-09-26 | REFINE | Can readers quote button labels? | No, excluded | — |
| 7 | 2026-09-26 | REFINE | Replay + assumptions A1–A9 | Accepted | — |
| 8 | 2026-09-26 | DESIGN | How does a Story-only block plug into the Editor? | Block-type registry in Editor, per-consumer `blockTypes` opt-in | — |
| 9 | 2026-09-26 | DESIGN | Target status/title are read-time info, HTML is stored | Static save-time render; no published/deleted check; reader gets a 404 on a dead choice, as with plain links today | #1, #4 (reader side) |
| 10 | 2026-09-26 | DESIGN | How does an author prepare an unpublished branch? | Per-choice "enabled" toggle; disabled choices are hidden from everyone on the reader page | — |
| 11 | 2026-09-26 | DESIGN | Empty-label fallback after target rename | Title frozen at last save of the holding chapter | #2 (partly) |
| 12 | 2026-09-26 | DESIGN | Keeping labels out of quotes | No opt-out stopgap. Quotability becomes opt-in per block in a prerequisite task (`quotable-blocks-opt-in/`); this task is BLOCKED until it is done | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

Confirmed by the user at replay (decision #7).

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Block offered only in story chapters, never News/StaticPage | REFINE | Yes |
| A2 | Switching to Simple refused while a choice block exists (like images) | REFINE | Yes |
| A3 | Whoever can edit the chapter can use the block; visibility follows the chapter | REFINE | Yes |
| A4 | Choice without target dropped on save; empty block dropped | REFINE | Yes |
| A5 | Labels don't count in word/character counts | REFINE | Yes |
| A6 | No notification, event, statistic, search, moderation change | REFINE | Yes |
| A7 | No cap on number of choices per block | REFINE | Yes |
| A8 | Buttons wrap/stack on mobile | REFINE | Yes |
| A9 | Label plain text, ~120 chars max | REFINE | Yes |
| A10 | `<x-editor::multi.block>` gets the enabled types via `@aware(['blockTypes'])` from the enclosing `<x-editor::multi>`, so plugin partials pass nothing | BUILD (2) | Yes |
| A11 | A stored `text`/`image` block is also skipped when its type is not in `blockTypes` (same rule as plugins); no consumer does this today | BUILD (2) | Yes |
| A12 | A choice targeting its own holding chapter is rendered with the slug/title from *before* that save (targets are read before the update); a rename in the same save leaves a stale slug, absorbed by the canonical 301 | BUILD (3) | Yes |
| A13 | Choice buttons reuse `<x-shared::button>` primary colours (`surface-primary`, `text-sm`, `rounded-md`) and are centred in the wrapper; VERIFY confirms the look | BUILD (3) | Yes |
| A14 | Choice rows are server-rendered (in a `chapter-choice-row` sub-partial, also used as the add-row `<template>`); the local Alpine scope holds no `choices` array but moves/removes rows in the DOM and re-indexes names — makes the stored state, the « Chapitre supprimé » option and the round-trip testable from PHP, and avoids `x-model` on dynamic selects | BUILD (4) | Yes |
| A15 | Removing the last choice of a block is allowed (the empty block is dropped on save, A4); row controls are named `add`/`up`/`down`/`remove` taking `$el` | BUILD (4) | Yes |
