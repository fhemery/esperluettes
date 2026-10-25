---
name: continue-task
description: Resume an in-progress feature in the loop-engineering backlog. Use when the user says "/continue-task", "where were we", "keep going on X", or asks to pick a task back up. Reads docs/Feature_Planning/BACKLOG.md, finds the WIP task, reconciles its real state from the files on disk, and resumes the loop.
---

# Continue an in-progress task

Same orchestrator as [`next-task`](../next-task/SKILL.md) — read that skill and
[`.agents/loop/README.md`](../../loop/README.md); only the selection and the
resume logic differ.

## 1. Select

Read [`docs/Feature_Planning/BACKLOG.md`](../../../docs/Feature_Planning/BACKLOG.md).
Never `git pull` or `git rebase` — the user keeps the working copy where it
should be.

- One `WIP:*` entry → that is the task.
- Several → normal when two sessions run in parallel worktrees, not an anomaly.
  Prefer the entry whose task folder *this* worktree's branch has been touching
  (`git log --oneline main.. -- docs/Feature_Planning/`); if that is ambiguous,
  list them and ask. Never resume another session's entry.
- None → say so and offer `/next-task`.
- If the user named a task, use it whatever its status.

## 2. Reconcile before resuming

**The files on disk win over the status column.** Determine the real step:

| Evidence | Real state |
|----------|------------|
| `CONTEXT.md` §1 still `_Not written yet._` | REFINE not finished |
| §1 written, §2 or §3 not | DESIGN+PLAN pending (a rated 1–2 task skips this: the refiner wrote both) |
| §3 written | BUILD — the phase index (§3.1) says which phase |
| all phases `DONE`, no shots outside `shots/checkpoint-*/` | VERIFY pending |
| VERIFY done, no `README.md` | WRAP pending |
| folder gone, entry in `## Done` | finished — nothing to continue |

A folder that still has `01-functional.md`/`DECISIONS.md` and no `CONTEXT.md`
predates the single-file layout: reconcile it from those files as before.

Also check reality, not just files:

- the current branch (`git rev-parse --abbrev-ref HEAD`). If it is `main`,
  create a task branch (`git checkout -b <type>/<folder>`) and say so. On any
  other branch, stay on it — assume it is the right one and do not pull, rebase
  or switch.
- `git status` and `git log` since the task started — was work done that the
  plan table does not reflect?
- the gate, if the last recorded phase claims to be `DONE` but you have any
  doubt. A red gate on resume means the previous session did not actually
  finish; dispatch a `phase-implementer` to fix it before moving on. Keep the
  output out of your thread:

  ```bash
  pnpm run gate --quick > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
  ```

If the files and the status field disagree, **fix the status field** and say so
in one line. Do not re-run a step whose artifact already exists — read it
instead.

## 3. Re-orient

Before doing anything, read in this order:

1. `README.md` if it exists (finished tasks are already compacted)
2. `CONTEXT.md` header (complexity, mode) and §0 Handoff — the agents'
   own notes on where things stand
3. the phase index (§3.1) when in BUILD

Read no more of `CONTEXT.md` yourself; the subagent you dispatch reads what it
needs. Never re-ask a question already in §4.

Then tell the user, in three lines: where the task stands, what you believe the
next action is, and anything that looks inconsistent.

## 4. Resume

Continue exactly as `next-task` §4 — including its orchestrator rule: you
dispatch PLAN / BUILD / VERIFY / WRAP to subagents and do not edit code
yourself. Start at the reconciled step and honour the entry's mode:

- `interactive` — REFINE and DESIGN+PLAN chain without stopping (each subagent
  asks the user only about blocking points); stop after PLAN for the go before
  BUILD, as in `next-task` §4. **BUILD chains** its remaining
  phases in this chat, one `phase-implementer` each (`visual-verifier` for a
  checkpoint row), two lines of report per phase, and stop once they are all `DONE` (or when a §5 condition fires).
- `auto` — keep dispatching the remaining steps until the task is `DONE` or a
  `next-task` §5 stop condition fires. Still one subagent per step and per
  phase; `auto` drops the approval stops, not the context boundaries.

If the task was interrupted **mid-phase** (files changed but the phase's
acceptance criteria are not met), do not restart the phase from scratch:
dispatch a `phase-implementer` told to read the diff, finish what is missing,
and run the gate.
