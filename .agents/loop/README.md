# Loop engineering — the protocol

Every feature in this project goes through the same six steps. This file is the
tool-agnostic definition of that loop: any coding agent (Claude Code, Codex,
Cursor, a human) can run it by reading this file. The `.agents/skills/*` folders
hold the detailed instructions for each step; `.claude/` only contains thin
shims that point at them.

`.agents/` is **static**: protocol, templates, references, per-step
instructions. All mutable state lives under `docs/Feature_Planning/` — the
backlog and the task folders.

## The entry points

| Command | Selector |
|---------|----------|
| `/next-task` | first row with status `TODO` in [`BACKLOG.md`](../../docs/Feature_Planning/BACKLOG.md) → start it at REFINE |
| `/continue-task` | first row with status `WIP:*` → resume at that step (ask if several are WIP) |
| `/add-task` | append a new row, write `00-request.md`, stop |

## The six steps

| Step | Skill | Runs | Produces |
|------|-------|------|----------|
| REFINE | `refine-feature` | **main thread** — it interviews the user | `01-functional.md` |
| DESIGN | `design-architecture` | **main thread** — the user picks tradeoffs | `02-architecture.md` |
| PLAN | `plan-phases` | subagent, user approves the result | `03-plan.md` |
| BUILD | `implement-phase` | one subagent **per phase** | code + tests, green gate |
| VERIFY | `verify-visually` | subagent (uses `run-app`) | `shots/`, filled QA checklist |
| WRAP | `wrap-task` | subagent | `README.md`, backlog updates |

A subagent has no channel to the user: it can only report back. That is why
REFINE and DESIGN are *not* subagents. They may still spawn read-only research
agents to answer "how does X already work?" before asking the user a question
the codebase already answers.

## Status vocabulary

`docs/Feature_Planning/BACKLOG.md` status column:

- `TODO` — not started
- `WIP:<STEP>` — in progress, `<STEP>` ∈ `REFINE DESIGN PLAN BUILD VERIFY WRAP`;
  BUILD may carry a phase counter, e.g. `WIP:BUILD (3/7)`
- `BLOCKED:<reason>` — waiting on something outside the loop
- `DONE` — wrapped

**The artifacts are the real checkpoints.** If `02-architecture.md` exists,
DESIGN happened, whatever the status column says. `/continue-task` reconciles
the two and trusts the files. Update the status column at every step boundary so
an interrupted session resumes cheaply.

## Modes

The `Mode` column controls how often the loop stops for the user:

- `interactive` (default) — stop at every step boundary for approval
- `auto` — run all six steps without stopping; report at the end only. For
  bugfixes and small chores. REFINE and DESIGN degrade to "write down the
  assumptions and continue" rather than interviewing.

The orchestrator proposes a mode when the task is created; the user overrides
it by editing the column.

## Task folder

`docs/Feature_Planning/<slug>/`

```
00-request.md        the raw ask, written by the user (free form, may be 3 lines)
01-functional.md     REFINE output — what the feature does, decisions table
02-architecture.md   DESIGN output — how it is built, tradeoffs locked
03-plan.md           PLAN output — phases with deliverables/tests/acceptance
DECISIONS.md         append-only log; every answer the user gave, with its date
README.md            WRAP output — the compact record; **the only file agents
                     should load by default** once the task is DONE
shots/               screenshots produced by VERIFY
```

`README.md` exists so a finished feature costs ~100 lines of context instead of
~1000. The phase documents stay in the folder as history; agents read them only
when the README points them there.

Templates for each file live in [`templates/`](./templates/).

## Gate

Every BUILD phase and the VERIFY step end on a green gate:

```bash
npm run gate            # deptrac + php tests + vitest + vite build
npm run gate -- --quick # skip the asset build (faster inner loop)
```

Honours `LOCAL_RUNNER` (`php` or `sail`) like the husky hooks. A phase is not
finished until the gate is green — this is rule #4 of `AGENTS.md` made
mechanical.

## Non-negotiables inherited from AGENTS.md

1. Don't assume. Don't hide confusion. Surface tradeoffs.
2. Minimum code that solves the problem. Nothing speculative.
3. Touch only what you must. Clean up only your own mess.
4. Define success criteria. Loop until verified.
