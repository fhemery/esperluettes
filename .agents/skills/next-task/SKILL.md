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
Never `git rebase`. The only pull is the one on `main` in §2.

- **If the user named a task** (`/next-task annotations`), that is the task,
  whatever its position or status. This is the normal form when two sessions
  run in parallel — see "Parallel sessions" in
  [`.agents/loop/README.md`](../../loop/README.md).
- Otherwise take the **first entry with status `TODO`**, top to bottom. Before
  doing so, check `git worktree list`: if the repository has more than one
  worktree, another session may be running, and picking an entry unilaterally
  is how two sessions end up on the same one. Say which entry you would take
  and ask the user to confirm, rather than guessing.
- If an entry is `WIP:*`, do not silently skip it — tell the user there is a
  task in progress and ask whether to finish it (`/continue-task`) or start the
  new one anyway.
- If there is no `TODO` entry, say so and stop. Do not invent a task.
- `BLOCKED:*` entries are skipped, but mention them once.
- Once you have selected the task, if you can, change the conversation title to `Task: <Task name>`

## 2. Set up

- **If the user named the branch to work on**, switch to it (or stay on it) and
  do not pull, rebase or switch further.
- **Otherwise start from a fresh `main`**, whatever branch you are on:
  1. `git status --short` must be clean — if not, stop and ask.
  2. `git checkout main && git pull --ff-only`. If `main` is checked out in
     another worktree, use `git fetch origin main` and branch from
     `origin/main` instead.
  3. `git checkout -b <type>/<folder>` and say so.
- Ensure `docs/Feature_Planning/<folder>/` exists.
- If `00-request.md` is missing, ask the user for the request before doing
  anything else — the loop has no input otherwise. Offer
  [`templates/00-request.md`](../../loop/templates/00-request.md).
- Ensure `CONTEXT.md` exists (`add-task` creates it); if not, copy
  [`templates/CONTEXT.md`](../../loop/templates/CONTEXT.md). It is the task's
  single working file — see the loop README. A folder from before this layout
  (`01-functional.md`, …, `DECISIONS.md`) is finished in its own layout; do not
  migrate it.
- Set the backlog status to `WIP:REFINE`.

## 3. Confirm the mode

Judge the size from `00-request.md` and propose:

- **`auto`** for a bugfix, a copy change, a one-file tweak — anything where the
  functional question is already answered by the request itself.
- **`interactive`** for anything that adds a table, a route, a permission rule,
  or a user-visible flow.

State your judgement in one sentence and the mode you are taking. In
`interactive` mode wait for the user; in `auto` mode carry on. Write the mode
into the backlog entry.

## 4. Run the loop

**You are an orchestrator, not an implementer.** You select, dispatch, update
the backlog, and report. You do not write code, tests or planning artifacts
yourself — every file-modifying step runs in a subagent with a fresh context.
Reaching for Edit or Write in this thread (outside `Feature_Planning`) means you have stopped orchestrating. See "Context discipline"
in [`.agents/loop/README.md`](../../loop/README.md) for why this is the loop's
most expensive rule to break.

| Step | How to run it |
|------|---------------|
| REFINE | spawn the `feature-refiner` agent (asks the user only if a point is blocking). It rates the task's complexity /5; on **1–2 it also writes the design and plan itself**, so skip the next row |
| DESIGN+PLAN | spawn the `architect-planner` agent (complexity 3–5; asks the user only about a blocking tradeoff; on **5** it splits the task and files the remaining slices in the backlog) |
| BUILD | spawn one `phase-implementer` agent **per phase** of the plan (`CONTEXT.md` §3); a checkpoint row (`2v`) goes to `visual-verifier` instead — see below |
| VERIFY | spawn the `visual-verifier` agent |
| WRAP | spawn the `task-wrapper` agent |

Never run any step in this thread, **including the small ones**. Measured on
this project's transcripts, a request in an orchestrator thread re-reads
~80k tokens of context (never under ~60k), while a subagent request re-reads
~50k on average and starts at ~11k; running a 30-request design+plan inline
costs roughly 2.5M tokens against ~1.5M in a subagent. A small task saves
tokens by having **fewer agents** (the refiner's fast path), not by borrowing
this thread.

**Models.** Pass `model:` on each spawn; the agent files default to `sonnet`
(except `architect-planner`, `opus`), so only the exceptions need an override.
Do not guess a rating from the request: REFINE's own rating, written in the
`CONTEXT.md` header, is the authority once it exists.

| Spawn | Model |
|-------|-------|
| `feature-refiner` | `sonnet`; `opus` only if the backlog entry carries a `complexity:4` or `complexity:5` hint (set by `add-task`) |
| `architect-planner` | `opus` (it only runs at 3–5) |
| `phase-implementer` | `sonnet`; `opus` for a phase whose index row in `CONTEXT.md` §3.1 is marked **risky** |
| `visual-verifier`, `task-wrapper` | `sonnet` |

Rated 4–5 or refined with two or more blocking questions, `architect-planner`
starts by reviewing §1 (`design-and-plan`), because a Sonnet refiner wrote it.

REFINE and DESIGN+PLAN subagents ask the user themselves when a point is
blocking, and the others need no user input. If a subagent reports a
blocking point instead of asking (its host cannot ask), you ask the user — one
question at a time, with the subagent's recommended default first — append the
answer to `CONTEXT.md` §4, and re-dispatch the same step. Read `CONTEXT.md`
only for its header, §0 Handoff and the phase index (§3.1) — never whole.
Only if the host genuinely cannot spawn agents (the
spawn/Task capability is missing) do you run them here via their skills, and
then you say so explicitly. A model refusal, rate limit, or premium-credit
exhaustion is not that case — pick another available model and keep spawning.


**Checkpoint rows.** A phase-index row whose id ends in `v` (see `plan-phases`)
changes no code. Spawn `visual-verifier` on that checkpoint's section only; it
builds assets, checks the listed existing consumers, writes
`shots/checkpoint-<id>/` and appends a PASS/FAIL result. PASS → mark the row
`DONE` and keep chaining. FAIL → spawn a `phase-implementer` to fix the
preceding phase from the checkpoint's findings, then run the checkpoint again —
no user stop, in either mode. A second FAIL for the same reason is a §5 stop.

Delegate research to read-only `Explore` agents too. "How does the FAQ image
flow work?" costs the orchestrator one paragraph instead of six file reads it
will then carry for the rest of the session.

Dispatch each step in order. After each one, update the backlog status — your
own entry only, never another session's — then:

- `interactive` — **REFINE → DESIGN+PLAN chain without stopping**: after each,
  give a two-line progress report (complexity rating, assumptions made) and
  dispatch the next. The step's own subagent already asked the user whatever was
  blocking. Stop once the plan exists, summarise the assumptions (the ones the
  user is likeliest to reverse first), the phase index and any tasks the split
  sent to the backlog, and wait for the go before
  BUILD; if the user wants a fresh chat, `/clear` then `/continue-task`.
  **BUILD chains its phases in one chat.** Each phase runs in
  its own `phase-implementer`, so the orchestrator only ever carries a two-line
  report per phase — clearing between them buys nothing and costs a re-read of
  the backlog, the plan and `CONTEXT.md` every time. Stop between phases only
  when a §5 condition fires, or when a phase contradicted the plan in a way the
  user should arbitrate before the next one builds on it. Stop again once all
  phases are `DONE`, before VERIFY.
- `auto` — **keep dispatching the remaining steps without stopping for
  approval.** Do not ask the user to open a new chat unless a stop condition in
  §5 fires. Every judgement call lands in the "Assumptions" table of
  `CONTEXT.md` §4 (the agents write it). A short progress line per step/phase is enough.

`auto` changes when you stop for the *user*, never how you dispatch. One
subagent per step and per phase either way — the whole point is that your own
thread stays small enough to run the loop to the end.

Between BUILD phases, in both modes, report the phase result in two lines and
keep going — the per-phase approval gate is only for `interactive` mode when the
phase changed something the plan did not foresee. Keep each phase report to what the
next phase needs: what shipped, gate result, anything that contradicted the
plan. Never paste a subagent's full output into your thread.

## 5. Stop conditions

Stop and ask the user when:

- a step reports a **blocking** point it could not ask itself (a point it did
  ask, and got answered, is not a stop);
- the gate (`pnpm run gate`) fails twice on the same phase for the same reason;
- reality contradicts the plan (a needed API does not exist, a decision turns
  out to be unimplementable);
- you are about to touch a domain that neither `CONTEXT.md` §1 nor §2
  mentions;
- in `auto` mode, a tradeoff is genuinely expensive to reverse (see
  `design-architecture` auto section) — then stop for that decision only, then
  resume chaining.

WRAP archives the task itself, in both modes, without asking (`wrap-task` §6):
the folder becomes `_done/<slug>.md` and the entry moves to `## Done`. Report
the outcome, including the archive path. Do **not** start the next backlog
`TODO` unless the user asks (`/next-task`).
