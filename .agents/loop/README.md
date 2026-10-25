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
| `/next-task` | first entry with status `TODO` in [`BACKLOG.md`](../../docs/Feature_Planning/BACKLOG.md) → start it at REFINE |
| `/continue-task` | first entry with status `WIP:*` → resume at that step (ask if several are WIP) |
| `/add-task` | append a new entry, write `00-request.md`, stop |

## The six steps

| Step | Skill | Runs | Produces |
|------|-------|------|----------|
| REFINE | `refine-feature` | subagent `feature-refiner` — asks the user only about blocking points; rates complexity /5 | `CONTEXT.md` §1 |
| DESIGN+PLAN | `design-and-plan` (→ `design-architecture`, `plan-phases`) | one subagent `architect-planner` — asks only about blocking tradeoffs; splits a 5/5 task | `CONTEXT.md` §2, §3 |
| BUILD | `implement-phase` | one subagent **per phase**; a checkpoint row (`2v`) runs `verify-visually` instead | code + tests, green gate |
| VERIFY | `verify-visually` | subagent (uses `run-app`) | `shots/`, filled QA checklist |
| WRAP | `wrap-task` | subagent — opens with a retro | `README.md`, backlog updates, `loop-improvements/` findings |

The step names stay six because they are what the backlog status and the
vocabulary use; DESIGN and PLAN are *run* by one agent (they used to hand off
through two documents that then drifted).

**Complexity routes the early steps.** The refiner rates the task 1–5 (table in
`refine-feature`):

- **1–2 — fast path.** The refiner, still in its own context, writes §2 and §3
  too (compact, usually one phase). One agent instead of two: one startup, one
  read of the spec. BUILD is next.
- **3–4** — `architect-planner` takes DESIGN+PLAN.
- **5** — same, and it tries to **split** the work: it keeps the first
  self-sufficient slice and files the rest straight into the backlog with
  `add-task` (each request self-contained, because this folder is deleted at
  WRAP). No honest first slice → no split.

**Complexity also picks the model**, per spawn (table in `next-task` §4).
Defaults are the light model; the strong one is used for `architect-planner`
(only runs at 3–5), for a refiner whose backlog entry carries a
`complexity:4`/`5` hint (an optional guess written by `add-task` when the task
is filed), and for BUILD phases the plan marks **risky**. The orchestrator never
guesses a rating from the request itself: it only has three lines to go on, and
under-rating costs more than over-rating — REFINE's rating, made on a real spec,
decides everything after REFINE.

The fast path is deliberately *not* "run it in the orchestrator's thread":
an orchestrator request re-reads ~80k tokens (≥60k at minimum) against ~50k on
average in a subagent, so inline work costs more per request, not less.

REFINE and DESIGN+PLAN subagents can ask the user a question themselves
(Claude Code: `AskUserQuestion`), but **only about a blocking point** — one that
is the user's call, could reasonably go either way, and is costly to undo. With
no blocking point they write their section, record every judgement call as an
assumption in `CONTEXT.md` §4, and the orchestrator dispatches the next step
immediately, without waiting for the user. They may spawn read-only research
agents to answer "how does X already work?" before asking anything the
codebase already answers.

Where the host cannot let a subagent ask (a tool without that capability), the
subagent stops at its first blocking point and reports it with a recommended
default; the orchestrator asks the user and re-dispatches. Nothing is guessed
silently.

## Status vocabulary

`docs/Feature_Planning/BACKLOG.md` status field, in each entry:

- `TODO` — not started
- `WIP:<STEP>` — in progress, `<STEP>` ∈ `REFINE DESIGN PLAN BUILD VERIFY WRAP`;
  BUILD may carry a phase counter, e.g. `WIP:BUILD (3/7)`
- `BLOCKED:<reason>` — waiting on something outside the loop

There is no `DONE` status sitting on an active entry: WRAP's last act is to
archive it — see `## Done` in `BACKLOG.md` and "Task folder" below.

**The artifacts are the real checkpoints.** If `CONTEXT.md` §2 is written,
DESIGN happened, whatever the status field says. `/continue-task` reconciles
the two and trusts the files. Update the status field at every step boundary so
an interrupted session resumes cheaply.

## Modes

The `Mode` column controls how often the loop stops for the user:

- `interactive` (default) — REFINE and DESIGN+PLAN chain without stopping as
  long as none hits a blocking point (when one does, that subagent asks the
  user, then the chain resumes). The loop then stops once, after PLAN, so the
  user can review the accumulated assumptions before BUILD; BUILD phases chain
  as before and stop before VERIFY.
- `auto` — run all remaining steps without stopping for approval; report at the
  end only. For bugfixes and small chores. REFINE and DESIGN never interview:
  they write down the assumptions and continue. Only pause if a blocking
  question or other stop condition fires. Do not auto-start the next backlog
  `TODO`.

`auto` removes the **approval** stops, not the **context** boundaries. It still
dispatches one subagent per step and per BUILD phase, exactly as `interactive`
does. An `auto` task that runs twelve phases inline in the orchestrator's thread
is the single most expensive thing this loop can do — see "Context discipline".

The orchestrator proposes a mode when the task is created; the user overrides
it by editing the entry.

## Light path

For a task that changes only tooling, scripts, skills or docs — no app code, no
UI — once REFINE has settled the spec, DESIGN, PLAN and VERIFY add nothing.
Run: REFINE → direct edits (the orchestrator may edit; rule 1 of "Context
discipline" targets app code) → green gate → light WRAP (README, backlog,
retro). Propose it when creating the task; the user confirms. Any app code or
UI change puts the task back on the six steps.

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
2. **One step per subagent, one phase per subagent.** REFINE and DESIGN+PLAN
   run in their own subagents, so DESIGN starts from `CONTEXT.md` §0, §1 and §4
   — about 8k tokens — not from the REFINE interview transcript
   that produced them. That transcript is usually the largest object in the
   window and it carries no information the artifact does not; keeping it in a
   subagent keeps it out of the orchestrator.
3. **Command output is not a document.** Never let a full test run or gate run
   land in the transcript; it stays there and is re-read for the rest of the
   session. Redirect and read only what failed:

   ```bash
   pnpm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
   ```

The artifacts exist to make these boundaries cheap. A step that cannot resume
from its predecessor's artifact alone is a badly written artifact — fix the
artifact rather than keeping the thread alive.

## Task folder

While a task is active, everything lives in `docs/Feature_Planning/<slug>/`:

```
00-request.md        the raw ask, written by the user (free form, may be 3 lines)
CONTEXT.md           the single working file — see below
README.md            WRAP output — the compact record
shots/               screenshots produced by VERIFY
```

**`CONTEXT.md` is the one file agents share.** Its sections: §0 Handoff,
§1 functional spec, §2 architecture, §3 plan, §4 decisions & assumptions,
§5 build & verify log. Each agent reads §0 first and only the other sections it
needs (BUILD: its own phase of §3 plus the §2 subsections that phase names —
never the whole file), then writes its own section **and one §0 Handoff
entry**: what the next agent needs that the sections do not say — a trap, the
assumption most likely to be reversed, something deliberately left out. A
section still reading `_Not written yet._` is how `/continue-task` knows where
the task stands. The orchestrator reads only the header, §0 and the phase index.

At WRAP, `CONTEXT.md` is compacted into `README.md` (an index of what the code
now does) and the folder is archived. `README.md` exists so a finished feature
costs ~100 lines of context instead of ~1000.

The template is [`templates/CONTEXT.md`](./templates/CONTEXT.md); `add-task`
copies it into every new task folder. Folders from before this layout
(`01-functional.md` … `DECISIONS.md`) are finished in their own layout.

## The `_done/` archive

WRAP's last act (§6 of `wrap-task`) is to reduce the folder to that one
`README.md`, move it to `docs/Feature_Planning/_done/<slug>.md` — a flat file,
no subfolder — and delete the rest. This happens **by default, in both modes,
without asking**. `docs/Feature_Planning/<slug>/` then stops
existing; `00-request.md`/`CONTEXT.md` are not kept, since step 5
already folded anything a future reader needs into the domain's own docs, and
git keeps the rest.

`_done/` is not loaded by default — it is browsed the way you'd browse closed
PRs, when working in a related area or chasing why something is the way it is.
REFINE and DESIGN check it for a similar past feature before asking the user a
question a prior decision already answered (see "the nearest analogous
feature" in `refine-feature` and `design-architecture`).

## Parallel sessions

Two sessions can run the loop at once, each in its own git worktree. Setting one
up is the user's job, not an agent's — see
[`docs/Working_With_Agents.md`](../../docs/Working_With_Agents.md). What follows
is only what changes for *you* when a second session exists.

The checkouts isolate themselves: Compose derives its project name from the
directory basename, so each worktree has its own containers and MySQL volume,
and PHP tests run on in-memory SQLite (`phpunit.xml`). Two gates never contend.

What is *not* isolated is [`BACKLOG.md`](../../docs/Feature_Planning/BACKLOG.md).
Task folders are per-slug and never collide; the backlog is a list where each
task is a single line, deliberately not a markdown table — a table's column
padding gets rewritten on every edit, which turns unrelated edits from two
branches into unreadable merge conflicts. Two rules keep the list manageable:

**The user names the task, the session does not choose it.** When more than one
session is running, `/next-task <folder>` is given the folder explicitly:

```
/next-task annotations
```

Selection is the user's, made once, out loud, before either session starts —
there is no protocol for two agents to agree on an entry, because they never race
for one. A bare `/next-task` still takes the first `TODO`, and is fine for a
single session; with two open it is a bug, and the orchestrator says so rather
than guessing.

**Do not run overlapping tasks together.** The backlog's status column records
the overlaps on purpose — `chapters-multi-edit/` and `annotations/` both touch
per-block anchoring, and `editor-domain-visual-qa/` is meant to land before
`chapters-multi-edit/` moves the DOM again. Two sessions on tasks like those
merge badly whatever the backlog mechanics. Pick rows from different domains.

Status updates then need no ceremony: edit the entry and let the change ride
along with the step's normal commit — no pull, no rebase, once the task
branch exists (`/next-task` pulls `main` only to create it). Each
session only ever touches its own entry. They still land on two branches, so if
the entries sit within a few lines of each other git may raise a one-line
conflict when the second branch merges — take both entries and move on.

## Skills the loop leans on

The steps do not restate procedures that already exist. `implement-phase` and
`wrap-task` defer to `commit` for the commit conventions, and to `add-event`,
`add-notification`, `add-setting`, `fix-deptrac` and `document-domain` for the
recurring pieces of work. `verify-visually` defers to `run-app` for the browser.

**Know-how lives in the skills.** The agents have no private memory: a trap an
agent discovers — a command that misbehaves, an environment quirk — belongs in
the skill or doc that would have prevented it, where every tool and worktree
sees it. WRAP's retro collects them in `loop-improvements/` rather than letting
them be fixed ad hoc.

**Refactor first, then checkpoint.** `plan-phases` puts any reshaping of
existing code in its own no-behaviour-change phase(s) before the feature, and
follows each such phase with a checkpoint row that `visual-verifier` runs
against the existing consumers. A regression is then traced to the phase that
caused it, before anything builds on top.

## Gate

Every BUILD phase and the VERIFY step end on a green gate:

```bash
pnpm run gate            # docs + deptrac + php tests + vitest + e2e type-check + vite build
pnpm run gate --quick    # skip the asset build (faster inner loop)
```

The `docs` step enforces that no `app/Domains/**/{README,AGENTS}.md` and no
`docs/adr/**` references `docs/Feature_Planning`, and that every relative
markdown link resolves.

Honours `LOCAL_RUNNER` (`php` or `sail`) like the husky hooks. A phase is not
finished until the gate is green — this is rule #4 of `AGENTS.md` made
mechanical.

**Every step ends on a green gate and a commit, not just BUILD.** REFINE,
DESIGN, PLAN and WRAP all write or delete files under `docs/Feature_Planning/`
— a renamed task folder, an absorbed sibling task, a deleted pre-loop doc — and
each of those can break a relative link or leave a stale reference sitting
uncommitted, invisible until some unrelated later change stumbles on it. Run
`pnpm run gate` (it skips the PHP/JS suites when nothing code-related changed,
so this is cheap) and commit the step's own artifact before handing back —
see the `commit` skill. Skip the commit only for a genuine mid-interview
scratch state, and say so.

## Non-negotiables inherited from AGENTS.md

1. Don't assume. Don't hide confusion. Surface tradeoffs.
2. Minimum code that solves the problem. Nothing speculative.
3. Touch only what you must. Clean up only your own mess.
4. Define success criteria. Loop until verified.
