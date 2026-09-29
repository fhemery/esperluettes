# Improve loop — functional specification

> REFINE output. Describes **what** changes, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Six improvements to the loop protocol (`.agents/loop/`), the step skills, the
agent shims and two scripts, taken together as the request asks. Their users are
the agents that run the loop and the user who arbitrates it. There are three
goals. Regressions from a shared refactor should surface at the phase that
caused them. Documented commands should work on the pinned pnpm 12. Lessons an
agent learns the hard way should end up in versioned skills, not lost or
misplaced.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Checkpoint row | A row in `03-plan.md`'s phase index, id suffixed `v` (e.g. `2v`). It checks existing behaviour and changes no code. Run by `visual-verifier`, not `phase-implementer`. |
| Refactor phase | A phase that reshapes existing code with **no behaviour change**, placed before the phases that add the feature. |
| Retro | The opening section of WRAP. It looks back on the task and extracts loop/skill improvements. |
| Collector entry | The standing backlog entry `loop-improvements/` whose `00-request.md` accumulates retro findings as numbered suggestions. |

## 3. Roles & visibility

N/A — no app user, role or permission is involved. The "actors" are the
orchestrator, the step subagents and the user arbitrating the loop.

## 4. Functional requirements

### 4.1 Refactor-first planning (request #3)

1. When a feature needs existing code reshaped, PLAN puts that reshaping in
   its own first phase(s), which change no behaviour. The feature phases come
   after it — "make the change easy, then make the easy change".
2. Refactor phases are called out as such in the phase index.

### 4.2 Checkpoint rows (request #1)

1. PLAN **must** insert a checkpoint row right after any phase that reshapes
   code with existing consumers (a refactor, or infrastructure other features
   already use). PLAN may add one elsewhere at its own discretion.
2. A checkpoint's plan section says which existing consumers and which e2e
   specs to check.
3. The orchestrator (`next-task` / `continue-task`) recognises a checkpoint row
   and dispatches it to `visual-verifier`, never to `phase-implementer`.
4. The verifier rebuilds assets first (see 4.4). It then checks the listed
   consumers, writes screenshots under `shots/checkpoint-<id>/`, and appends a
   PASS/FAIL result to the checkpoint's section.
5. **PASS** → the row is set `DONE` and BUILD keeps chaining.
6. **FAIL** → the orchestrator dispatches a `phase-implementer` to fix the
   preceding phase, then re-runs the checkpoint. It does not stop for the user,
   even in `interactive` mode. If the checkpoint fails twice for the same
   reason, that is a stop condition.
7. A checkpoint counts in the BUILD counter `WIP:BUILD (n/m)`.
8. `continue-task`'s state reconciliation must not mistake checkpoint shots for
   VERIFY having run.

### 4.3 pnpm `--` in documented commands (request #2)

1. Every documented `pnpm run <script> -- <args>` is rewritten to
   `pnpm run <script> <args>`. This covers skills, the loop README, docs,
   `e2e/README.md`, `.cursor/commands/` and script usage texts.
2. The scripts themselves are not changed to tolerate a stray `--`.
3. Running a single e2e spec file is documented with the form that actually
   filters.

### 4.4 Stale compiled assets (request #4)

1. The gate treats a change to any `*.blade.php` as triggering the asset build,
   because Tailwind scans Blade. `--quick` still skips the build.
2. VERIFY and checkpoints always build assets before driving the browser.
3. When a style assertion fails, the verifier first checks that the class
   exists in the built CSS before it reports a defect.

### 4.5 Know-how in skills, not agent memory (request #5)

1. The loop agents no longer have persistent agent memory. Know-how lives in
   the versioned skills and docs, shared by every tool and worktree.
2. The stray note's content is folded into `verify-visually`, `run-app` and
   `e2e/README.md`:
   - build before verifying, and grep the built CSS before reporting a styling
     defect;
   - how to run a single spec file;
   - after an e2e run, the :8080 server keeps its data; the run-app driver can
     point at it (`APP_BASE_URL`) with the seeded `<role>@e2e.test` accounts,
     whose CGU are already accepted, to take checklist screenshots.
3. The existing local agent-memory notes are reviewed: what still holds is
   folded into the relevant skill or doc, the rest is dropped.
4. Explain, in the design, why the memory path resolved inside the task folder,
   so the removal is known to close that hole.

### 4.6 Retro at WRAP (request #6)

1. WRAP's first section is a retro, done by `task-wrapper` from the artifacts.
   It looks at superseded decisions, plan contradictions, gate re-runs, fix
   commits, FAIL checkpoints, and anything an agent had to discover.
2. Each finding becomes a numbered suggestion appended to
   `docs/Feature_Planning/loop-improvements/00-request.md`, in the same shape as
   this task's request.
3. If the collector entry is missing, WRAP creates it (backlog entry + folder),
   as `/add-task` would.
4. WRAP's report lists the findings it filed.
5. The loop keeps six steps; the status vocabulary does not change.

## 5. Lifecycle

- The collector entry lives in the backlog as a `TODO` until the user chooses to
  run it. Once run and wrapped, it is archived like any task, and the next retro
  creates a fresh one.
- Checkpoint shots live in the task folder with the other shots and disappear
  with it at WRAP's archive.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — tooling only |
| Visibility / privacy | N/A — tooling only |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | §5 — collector entry and checkpoint shots |
| Media | N/A |
| Search | N/A |
| i18n | N/A — loop docs are in English |
| Mobile | N/A |
| Accessibility | N/A |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Agent memory policy | Drop `memory: project` from the agents; fold notes still true into skills/docs |
| 2 | Where RETRO sits | First section of WRAP, done by `task-wrapper`; still six steps |
| 3 | Where retro findings land | One collector backlog entry, `loop-improvements/00-request.md`, created if missing |
| 4 | When PLAN inserts a checkpoint | Required after any phase reshaping code with existing consumers; optional elsewhere |
| 5 | Stale assets | Both: gate builds on Blade changes, and VERIFY/checkpoints build first |
| 6 | pnpm `--` | Drop `--` in docs only; scripts unchanged |

## 8. Out of scope

- Making `add-task` or any other script tolerate a stray `--`.
- A separate RETRO step, a `WIP:RETRO` status, or a user interview at retro.
- Access to chat transcripts from the retro — it works from artifacts and git.
- A tool-agnostic agents folder or Codex equivalents of the Claude agents.
- Changing the e2e runner itself (`pnpm run e2e` keeps its current script).

## 9. Open questions

- **non-blocking** — Does `pnpm run browser:drive` (run-app) misbehave with a
  forwarded `--`? It is irrelevant once the docs drop it, but the check will
  confirm the rewrite is safe.
