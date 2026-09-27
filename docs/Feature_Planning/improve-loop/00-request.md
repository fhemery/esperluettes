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
3. **PLAN orders refactoring before the feature.** When a feature needs existing
   code reshaped, the plan's first phases refactor with no behaviour change,
   and only the following phases add the new feature — "make the change easy,
   then make the easy change". Pairs with suggestion 1: the checkpoint sits at
   the boundary between the two.
4. **The gate passes with stale compiled assets.** The gate skips the vite
   build when the branch only changes Blade files, but a new Tailwind class in
   a Blade view needs a rebuild to exist in `public/build`. In
   `multiedit-chapter-switch-block` VERIFY first saw broken styling
   (underlined, indented buttons) because a class was missing until
   `pnpm run build`. Fix: treat Blade changes as triggering the asset build
   (Tailwind scans them), and/or have `verify-visually` / `e2e` rebuild first.
5. **Browser-verification know-how belongs in the skills, not in agent
   memory.** During `multiedit-chapter-switch-block` VERIFY, the
   `visual-verifier` saved a memory note — inside the task folder
   (`docs/Feature_Planning/<slug>/.claude/agent-memory/…`) instead of
   `.claude/agent-memory/visual-verifier/`, so it was deleted at WRAP. What it
   held, beyond suggestion 4:
   - when a style assertion fails, grep `public/build/assets/app-*.css` for
     the class before reporting a defect (stale build, see 4);
   - `pnpm run e2e -- <filter>` ran the whole suite; run one file with
     `pnpm exec playwright test e2e/tests/features/<slug>.spec.ts`;
   - after `pnpm run e2e`, the e2e server on :8080 keeps the post-run data:
     point the run-app driver at it with `APP_BASE_URL=http://localhost:8080`
     and the seeded `<role>@e2e.test` accounts (CGU already accepted by
     `auth.setup`) to take checklist screenshots.

   Fold these into `verify-visually` / `run-app` / `e2e/README.md`, and find
   out why the agent's memory path resolved into the task folder.
6. **Add a RETRO step to the loop.** After VERIFY (or as the first part of
   WRAP), look back on the task: what slowed it down, what the user had to
   correct, what an agent had to discover the hard way (like 4 and 5). Each
   finding becomes a numbered suggestion appended to this request — or a new
   loop-improvement task once this one is done — so improvements come out of
   every task by default instead of being noted by hand.

## Why

Regressions from a shared refactor are spotted early and traced to the phase
that caused them, instead of surfacing at the final VERIFY once later phases
have built on top.

## Constraints or ideas I already have

Collect suggestions here as they come up; do them together.

## Explicitly out of scope

