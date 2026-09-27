# Improve loop — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

A place to collect improvements to the loop protocol (`.agents/loop/`) and the
orchestrator skills (`next-task`, `continue-task`, step skills). Suggestions so
far:

1. **Checkpoint verification steps between BUILD phases.** PLAN may insert a
   "checkpoint" row in the phase index (e.g. `2v`) right after a refactor of
   shared infrastructure, before new features build on it. It is dispatched to
   the `visual-verifier` agent, changes no code, and sends any regression back
   to a `phase-implementer` as a fix of the preceding phase. The orchestrator
   must know how to run such a row. First applied by hand in
   `multiedit-chapter-switch-block/03-plan.md` (checkpoint 2v).
2. **`add-task` skill invocation is broken under pnpm 11.** The skill says
   `pnpm run add-task -- --slug=…`; pnpm 11 forwards the `--` to the script,
   which rejects it (`Unrecognized argument: --`). Works without the `--`.
   Check other skills/docs that use `pnpm run <script> -- …` (e.g.
   `pnpm run gate -- --quick`) for the same issue.

## Why

Regressions from a shared refactor are spotted early and traced to the phase
that caused them, instead of surfacing at the final VERIFY once later phases
have built on top.

## Constraints or ideas I already have

Collect suggestions here as they come up; do them together.

## Explicitly out of scope

