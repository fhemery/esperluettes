# <Task title> — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).
>
> A section still reading `_Not written yet._` has not been done — that is how
> `/continue-task` knows where the task stands.

**Complexity:** _unrated_ /5 · **Mode:** interactive|auto

## 0. Handoff

What the next agent needs that the sections below do not say: a trap found, the
assumption most likely to be reversed, something deliberately left out, what to
double-check. Newest entry first; 3–8 lines each; never a summary of the
section you just wrote.

### <STEP> → <next step> (<date>)

_Not written yet._

## 1. Functional spec

> REFINE. **What** the feature does, never **how**. Everything here is either
> confirmed by the user or a recorded assumption (§4).

### 1.1 Overview

Two or three sentences: what, for whom, the value.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| | |

New nouns, in French if user-facing (the app is French-only).

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | | |
| `user` (non-confirmed) | | |
| `user-confirmed` | | |
| Author / co-author of the target | | |
| Moderator | | |
| Admin | | |

### 1.4 Functional requirements

One sub-heading per flow, numbered steps, error and edge paths included.

### 1.5 Lifecycle

What happens to this data when the parent is deleted, unpublished or edited;
when the user is deactivated, reactivated or deleted; what the UI shows.

### 1.6 Cross-cutting concerns

One line per item of
[`cross-cutting-checklist.md`](../../../.agents/loop/references/cross-cutting-checklist.md);
inapplicable ones as "N/A — <reason>".

| Concern | Decision |
|---------|----------|
| Roles | |
| Visibility / privacy | |
| Settings | |
| Notifications | |
| Domain events | |
| Statistics | |
| Moderation | |
| Lifecycle / cascade | |
| Media | |
| Search | |
| i18n | |
| Mobile | |
| Accessibility | |

### 1.7 Out of scope

Explicit non-goals, so nobody builds them by accident.

### 1.8 Open questions

Each marked **blocking** or **non-blocking**. A blocking one is asked, never
left here.

_Not written yet._

## 2. Architecture

> DESIGN. **How** it is built: **shape and contracts, not a change list** —
> signatures, data shapes, enforcement points, deptrac edges. The file-by-file
> edits belong to §3 only, so the two cannot drift.

### 2.1 Domain placement

Which domain owns it and why; new domain justified against extending one.
One line per touched domain: minimum change, extension point or direct call.

### 2.2 Data model

Tables (columns, types, nullability, indexes; foreign keys only inside the
owning domain), model (attribute syntax), lifecycle rules mapped from §1.5.

### 2.3 PHP architecture

Public API · services · policy (enforcing §1.3) · events and listeners ·
routes/controllers/form requests (no `PATCH` — use `PUT`).

### 2.4 Frontend

Blade components, Alpine, JS/CSS layout; shared components reused.

### 2.5 Deptrac

New edges, each justified — or "no new edge".

### 2.6 Testing strategy

Integration (default), unit, vitest, and what only VERIFY can see.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | | | | |

### 2.8 Risks acknowledged

Known weak points, with the trigger that would make us revisit them.

_Not written yet._

## 3. Plan

> PLAN. BUILD reads **one phase at a time** plus §0 and the architecture
> sections that phase names — so each phase must stand alone: name the §2
> sections it needs, and state what earlier phases left behind.

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | | S/M | — | TODO |

S ≈ half a day, M ≈ 1–2 days, anything larger is split. Status: `TODO` ·
`WIP` · `DONE`; BUILD updates it (it is what lets `WIP:BUILD (3/7)` resume).
Checkpoint rows (`2v` — see `plan-phases`) are run by `visual-verifier` and
change no code: `| 2v | Checkpoint — <existing consumers> | S | 2 | TODO |`.

Working agreement: one phase = one commit; failing test first; no phase N+1
before N's acceptance holds; re-ordering is surfaced, not taken silently.

### Phase 1 — <name>

**Goal.** One sentence. **Reads.** §2.x, §2.y.

**Deliverables.** Files created or modified, by path.

**Tests.** Named integration tests, as they will exist.

**Acceptance.**
- ✅ Checkable statements, not intentions.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

Filled by VERIFY; written now, while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| | | |

### 3.3 Open items

What the plan assumes but has not confirmed; each names the phase that needs it.

_Not written yet._

## 4. Decisions

Append-only. Every question the user arbitrated, as given. BUILD reads this
before asking anything already settled. Never edit a row — add one that
supersedes it.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

Each is a decision the user may want to reverse — WRAP surfaces them.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| | | | |

## 5. Build & verify log

One line per phase or checkpoint as it lands (commit sha, gate result,
anything that contradicted the plan), then VERIFY's result block. Checkpoint
results go here too, under their id.

_Not written yet._
