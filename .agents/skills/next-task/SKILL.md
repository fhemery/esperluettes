---
name: next-task
description: Start the next feature in the loop-engineering backlog. Use when the user says "/next-task", "what's next", "start the next task", or asks to begin the top backlog item. Reads docs/Feature_Planning/BACKLOG.md, picks the first TODO, and runs the six-step loop from REFINE.
---

# Start the next task

You are the **orchestrator** of the loop defined in
[`.agents/loop/README.md`](../../loop/README.md). Read that file first if it is
not already in context — it defines the steps, statuses and folder layout.

## 1. Select

Read [`docs/Feature_Planning/BACKLOG.md`](../../../docs/Feature_Planning/BACKLOG.md).

- Take the **first row with status `TODO`**, top to bottom.
- If a row is `WIP:*`, do not silently skip it — tell the user there is a task
  in progress and ask whether to finish it (`/continue-task`) or start the new
  one anyway.
- If there is no `TODO` row, say so and stop. Do not invent a task.
- `BLOCKED:*` rows are skipped, but mention them once.
- Once you have selected the task, if you can, change the conversation title to `Task: <Task name>`

## 2. Set up

- Ensure `docs/Feature_Planning/<folder>/` exists.
- If `00-request.md` is missing, ask the user for the request before doing
  anything else — the loop has no input otherwise. Offer
  [`templates/00-request.md`](../../loop/templates/00-request.md).
- Create `DECISIONS.md` from its template.
- Set the backlog status to `WIP:REFINE`.

## 3. Confirm the mode

Judge the size from `00-request.md` and propose:

- **`auto`** for a bugfix, a copy change, a one-file tweak — anything where the
  functional question is already answered by the request itself.
- **`interactive`** for anything that adds a table, a route, a permission rule,
  or a user-visible flow.

State your judgement in one sentence and the mode you are taking. In
`interactive` mode wait for the user; in `auto` mode carry on. Write the mode
into the backlog row.

## 4. Run the loop

**You are an orchestrator, not an implementer.** You select, dispatch, update
the backlog, and report. You do not write code, tests or planning artifacts
yourself — every file-modifying step runs in a subagent with a fresh context.
Reaching for Edit or Write in this thread (outside `Feature_Planning`) means you have stopped orchestrating. See "Context discipline"
in [`.agents/loop/README.md`](../../loop/README.md) for why this is the loop's
most expensive rule to break.

| Step | How to run it |
|------|---------------|
| REFINE | invoke the `refine-feature` skill **in this thread** (it interviews the user) |
| DESIGN | invoke the `design-architecture` skill **in this thread** |
| PLAN | spawn the `feature-planner` agent |
| BUILD | spawn one `phase-implementer` agent **per phase** of `03-plan.md` |
| VERIFY | spawn the `visual-verifier` agent |
| WRAP | spawn the `task-wrapper` agent |

Never run REFINE or DESIGN in a subagent: a subagent cannot talk to the user, so
the interview would be lost. Conversely, never run PLAN / BUILD / VERIFY / WRAP
in this thread — they need no user input, so there is nothing to gain and a
whole context to lose. Only if the host genuinely cannot spawn agents do you run
them here via their skills, and then you say so explicitly.

Delegate research to read-only `Explore` agents too. "How does the FAQ image
flow work?" costs the orchestrator one paragraph instead of six file reads it
will then carry for the rest of the session.

Dispatch each step in order. After each one, update the backlog status, then:

- `interactive` — stop, summarise the step's output in a few lines, and tell the
  user to `/clear` and run `/continue-task`. **One step per chat**, or one BUILD
  phase. The next step reads the artifact, not this conversation.
- `auto` — **keep dispatching the remaining steps without stopping for
  approval.** Do not ask the user to open a new chat unless a stop condition in
  §5 fires. Record every judgement call in the "Assumptions" table of
  `DECISIONS.md`. A short progress line per step/phase is enough.

`auto` changes when you stop for the *user*, never how you dispatch. One
subagent per step and per phase either way — the whole point is that your own
thread stays small enough to run the loop to the end.

Between BUILD phases, report the phase result in two lines and keep going —
the per-phase approval gate is only for `interactive` mode when the phase
changed something the plan did not foresee. Keep each phase report to what the
next phase needs: what shipped, gate result, anything that contradicted the
plan. Never paste a subagent's full output into your thread.

## 5. Stop conditions

Stop and ask the user when:

- a step produces a **blocking** open question;
- the gate (`npm run gate`) fails twice on the same phase for the same reason;
- reality contradicts the plan (a needed API does not exist, a decision turns
  out to be unimplementable);
- you are about to touch a domain that neither `01-functional.md` nor
  `02-architecture.md` mentions;
- in `auto` mode, a tradeoff is genuinely expensive to reverse (see
  `design-architecture` auto section) — then stop for that decision only, then
  resume chaining.

In `auto` mode, when WRAP finishes with no open questions, mark the backlog
row `DONE` and report the outcome. Do **not** start the next backlog `TODO`
unless the user asks (`/next-task`).

Never mark a task `DONE` yourself in `interactive` mode — WRAP proposes it, the
user confirms.
