# Working with agents

Features in this project are built by coding agents running a fixed six-step
loop. This page is the operator's view: which command to type, when, and how to
run two sessions at once. The protocol itself — what each step produces and why
the boundaries are where they are — lives in
[`.agents/loop/README.md`](../.agents/loop/README.md).

`.agents/` is tool-agnostic: Claude Code, Codex or a human can run the loop by
reading it. `.claude/` holds only thin shims.

## The loop

```
REFINE → DESIGN → PLAN → BUILD → VERIFY → WRAP
```

| Step | Produces | Talks to you? |
|------|----------|---------------|
| REFINE | `01-functional.md` | yes — it interviews you |
| DESIGN | `02-architecture.md` | yes — you arbitrate tradeoffs |
| PLAN | `03-plan.md` | no |
| BUILD | code + tests, one subagent per phase | no |
| VERIFY | `shots/`, filled QA checklist | no |
| WRAP | `README.md`, backlog update | no |

Everything lives in `docs/Feature_Planning/<slug>/`, and the backlog that points
at those folders is
[`docs/Feature_Planning/BACKLOG.md`](Feature_Planning/BACKLOG.md).

## Commands

| Command | What it does |
|---------|--------------|
| `/add-task` | append a backlog row, write `00-request.md`, stop |
| `/next-task <folder>` | start that task at REFINE |
| `/next-task` | start the first `TODO` row — single-session only, see below |
| `/continue-task` | resume the `WIP` task at whatever step its files say it reached |

**One step per chat.** After each step, `/clear` and run `/continue-task`. The
next step reads the artifact on disk, not the conversation that produced it —
that is the whole reason the artifacts exist. A REFINE interview transcript is
usually the largest thing in the window and carries nothing `01-functional.md`
does not.

The `Mode` column in the backlog controls how often the loop stops:
`interactive` stops at every step for approval, `auto` runs to the end and
reports once. Use `auto` for bugfixes and chores.

## Definition of done

```bash
npm run gate            # docs + deptrac + php tests + vitest + vite build
npm run gate -- --quick # skip the asset build
```

Green, or the work is not finished. Every BUILD phase ends here.

## Running two sessions in parallel

Two agents can work the loop at the same time, each in its own git worktree.

### Create one

```bash
npm run worktree -- b        # ../esperluettes-b, on a new branch `b`
cd ../esperluettes-b
composer install && npm install
./vendor/bin/sail up -d
```

`npm run worktree` does the part that is easy to get wrong: it copies your
`.env` and shifts the host ports, because `docker-compose.yml` binds `:80`,
`:5173`, `:8080` and `:3306` on the host and two stacks cannot both have them.
With `--offset=1` (the default, incremented per existing worktree) the second
instance is on `:81`, `:5174`, `:8081`, `:3307`. It also gives the instance its
own `SESSION_COOKIE`: cookies are **not** port-scoped, so without that, logging
into one instance logs you out of the other.

The rest isolates itself. `docker-compose.yml` declares no `name:`, so Compose
derives the project name from the directory basename — a worktree in a
different directory gets its own containers, network and MySQL volume for free.
PHP tests run on in-memory SQLite (`phpunit.xml`), so two gates never contend
over a database either.

`vendor/` and `node_modules/` are gitignored and must be installed per worktree.
The Docker image is shared, so `sail up` does not rebuild it.

### Use it

**Say which task each session takes, before starting either.** That is the whole
coordination protocol:

```
session A:  /next-task annotations
session B:  /next-task statistics-profile
```

Agents do not pick rows for themselves when more than one session is open — a
bare `/next-task` in that situation makes them stop and ask. Selection is yours
because only you can see both sessions.

Pick tasks from **different domains**. The backlog's status column flags the
overlaps deliberately: `chapters-multi-edit/` and `annotations/` both rework
per-block anchoring, and `editor-domain-visual-qa/` is meant to land before
`chapters-multi-edit/` moves the DOM again. Two sessions on tasks like those
produce a merge nobody wants to review, however clean the mechanics are.

Task folders are per-slug, so the planning artifacts never collide. The only
shared file is `BACKLOG.md`, where each task is one line and each session edits
only its own row. If the two rows happen to sit within a few lines of each
other, git may raise a one-line conflict when the second branch merges — keep
both rows.

### Delete one

```bash
git worktree remove ../esperluettes-b     # add --force if it has stray files
git branch -d b                           # once merged
```

Stop its containers first if they are up (`./vendor/bin/sail down` from inside
it). Removing the directory by hand leaves a stale entry — `git worktree prune`
clears that.

### A trap worth knowing

The git stash stack is **shared across all worktrees**. A bare `git stash pop`
in one worktree can restore another's work. Use a temporary WIP commit instead,
or `git stash push -m "<tag>"` and `apply` the entry you find by that tag.
