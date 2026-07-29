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

- `interactive` (default) — stop at every step boundary for approval (typically
  one step — or one BUILD phase — per chat, then `/continue-task`)
- `auto` — run all remaining steps without stopping for approval; report at the
  end only. For bugfixes and small chores. REFINE and DESIGN degrade to "write
  down the assumptions and continue" rather than interviewing. Only pause if a
  blocking question or other stop condition fires. Do not auto-start the next
  backlog `TODO`.

`auto` removes the **approval** stops, not the **context** boundaries. It still
dispatches one subagent per step and per BUILD phase, exactly as `interactive`
does. An `auto` task that runs twelve phases inline in the orchestrator's thread
is the single most expensive thing this loop can do — see "Context discipline".

The orchestrator proposes a mode when the task is created; the user overrides
it by editing the column.

## Context discipline

Cost is `requests × context size`, and context only ever grows within a thread.
A 700-request session whose context climbs to 450k tokens pays that 450k on
every late request — roughly four times what the same work costs split across
fresh contexts. Measured on this project's own transcripts: 97% of all tokens
spent are re-reads of accumulated context. Written output is 0.6%. Reading
planning docs and source files, all sessions combined, is 0.1%.

So verbosity in the artifacts is nearly free, and **thread length is what
costs**. Three rules follow:

1. **The orchestrator never edits code.** `/next-task` and `/continue-task`
   select, dispatch, record status, and report. Every file-modifying step —
   PLAN, BUILD, VERIFY, WRAP — happens in a subagent with a fresh context. If
   you catch yourself opening an Edit in the orchestrator thread, that is the
   bug.
2. **One step per thread, one phase per subagent.** REFINE and DESIGN each end
   with `/clear`, then `/continue-task`. DESIGN needs `01-functional.md` and
   `DECISIONS.md` — about 8k tokens — not the REFINE interview transcript that
   produced them. That transcript is usually the largest object in the window
   and it carries no information the artifact does not.
3. **Command output is not a document.** Never let a full test run or gate run
   land in the transcript; it stays there and is re-read for the rest of the
   session. Redirect and read only what failed:

   ```bash
   npm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
   ```

The artifacts exist to make these boundaries cheap. A step that cannot resume
from its predecessor's artifact alone is a badly written artifact — fix the
artifact rather than keeping the thread alive.

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

## Skills the loop leans on

The steps do not restate procedures that already exist. `implement-phase` and
`wrap-task` defer to `commit` for the commit conventions, and to `add-event`,
`add-notification`, `add-setting`, `fix-deptrac` and `document-domain` for the
recurring pieces of work. `verify-visually` defers to `run-app` for the browser.

## Gate

Every BUILD phase and the VERIFY step end on a green gate:

```bash
npm run gate            # docs + deptrac + php tests + vitest + vite build
npm run gate -- --quick # skip the asset build (faster inner loop)
```

The `docs` step enforces that no `app/Domains/**/{README,AGENTS}.md` references
`docs/Feature_Planning`, and that every relative markdown link resolves.

Honours `LOCAL_RUNNER` (`php` or `sail`) like the husky hooks. A phase is not
finished until the gate is green — this is rule #4 of `AGENTS.md` made
mechanical.

## Non-negotiables inherited from AGENTS.md

1. Don't assume. Don't hide confusion. Surface tradeoffs.
2. Minimum code that solves the problem. Nothing speculative.
3. Touch only what you must. Clean up only your own mess.
4. Define success criteria. Loop until verified.
