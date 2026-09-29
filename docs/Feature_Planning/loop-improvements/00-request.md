# Loop improvements — request

*Standing collector. WRAP's retro (`wrap-task` §0) appends numbered
suggestions here; run the task when the list is worth a pass. Once wrapped, the
next retro starts a fresh one.*

## What I want

Improvements to the loop protocol (`.agents/loop/`), the step skills, the
agent shims, the docs and the dev scripts, surfaced by finished tasks.

1. **The e2e teardown stops the dev app.** *(improve-loop)* `e2e/support/sail.ts`
   runs `pkill -f 'artisan serve'` in the container, which also kills Sail's own
   `artisan serve` on `:80`. Every `pnpm run e2e` leaves `http://localhost`
   dead until `./vendor/bin/sail restart laravel.test`. Narrowing the pattern
   to `artisan serve --env=e2e` was checked by hand to spare `:80`. Fix the
   script, then drop the restart advice from `verify-visually` and
   `e2e/README.md`.
2. **The gate needs Sail even for a docs-only change.** *(improve-loop)* REFINE
   commits planning docs only, yet `pnpm run gate` failed on deptrac because
   Sail was not running; the PHP suite is already skipped for non-code
   changes. Skip deptrac the same way when no PHP file changed, or say in the
   loop README that Sail must be up before any step's gate.
3. **No documented light path for tooling/docs tasks.** *(improve-loop)* The
   user judged DESIGN, PLAN and VERIFY useless for a docs-and-one-script task
   and had the orchestrator edit directly after REFINE (DECISIONS #7). The loop
   only knows `interactive` and `auto`, both six steps with subagent BUILD.
   Consider a documented rule — e.g. REFINE → direct edits → light WRAP when
   the spec is settled and no app code changes — so the next such task does
   not have to argue for it.

## Why

So that what one task learns the hard way is fixed for the next, instead of
being rediscovered.

## Constraints or ideas I already have

Each item names the task it came from. Skip or merge items freely at REFINE.

## Explicitly out of scope

Findings about a feature itself — those go to the backlog as their own tasks.
