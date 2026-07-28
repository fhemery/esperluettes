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

- One `WIP:*` row → that is the task.
- Several → list them and ask which one.
- None → say so and offer `/next-task`.
- If the user named a task, use it whatever its status.

## 2. Reconcile before resuming

**The files on disk win over the status column.** Determine the real step:

| Evidence | Real state |
|----------|------------|
| no `01-functional.md` | REFINE not finished |
| `01-functional.md` exists, no `02-architecture.md` | DESIGN pending |
| `02-architecture.md` exists, no `03-plan.md` | PLAN pending |
| `03-plan.md` exists | BUILD — the phase index table says which phase |
| all phases `DONE`, `shots/` empty | VERIFY pending |
| VERIFY done, no `README.md` | WRAP pending |

Also check reality, not just files:

- `git status` and `git log` since the task started — was work done that the
  plan table does not reflect?
- `npm run gate -- --quick` if the last recorded phase claims to be `DONE` but
  you have any doubt. A red gate on resume means the previous session did not
  actually finish; fix that before moving on.

If the files and the status column disagree, **fix the column** and say so in
one line. Do not re-run a step whose artifact already exists — read it instead.

## 3. Re-orient

Before doing anything, read in this order:

1. `README.md` if it exists (finished tasks are already compacted)
2. `DECISIONS.md` — never re-ask a settled question
3. the artifact of the current step
4. the current phase of `03-plan.md`

Then tell the user, in three lines: where the task stands, what you believe the
next action is, and anything that looks inconsistent.

## 4. Resume

Continue exactly as `next-task` §4, starting at the reconciled step and honouring
the row's mode:

- `interactive` — one step (or one BUILD phase), then stop and ask for
  `/continue-task` in a new chat.
- `auto` — chain the remaining steps in this session until the task is `DONE`
  or a `next-task` §5 stop condition fires. No "open a new chat" pause between
  steps when nothing needs the user.

If the task was interrupted **mid-phase** (files changed but the phase's
acceptance criteria are not met), do not restart the phase from scratch: read
the diff, finish what is missing, then run the gate.
