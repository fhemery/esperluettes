# Improve loop

**Status:** DONE — 2026-09-29 · **Domain(s):** tooling (`.agents/`, `.claude/agents/`, `scripts/gate.js`, `e2e/README.md`) · no app code

## What it does

Six improvements to the loop, collected by hand across earlier tasks:
checkpoint rows after shared refactors, refactor-first planning, pnpm 12's
forwarded `--` removed from documented commands, the gate building assets when
Blade changes, agent know-how moved from private agent memory into versioned
skills, and a retro opening WRAP.

## Key behaviour

- **Checkpoint row** (`2v`): required by `plan-phases` right after any phase
  that reshapes code with existing consumers. The orchestrator dispatches it to
  `visual-verifier` ("Checkpoint mode" in `verify-visually`), and it changes no
  code. On FAIL, a `phase-implementer` fixes the preceding phase and the
  checkpoint re-runs, with no user stop; two FAILs for the same reason is a stop
  condition. It counts in `WIP:BUILD (n/m)`.
- **Refactor first:** reshaping lives in its own no-behaviour-change phase(s)
  before the feature phases.
- **Gate:** a `*.blade.php` change triggers the vite build (not vitest).
  VERIFY always runs `pnpm run build` first.
- **No agent memory:** `memory: project` removed from all five agents. The old
  path resolved relative to the agent's cwd, so the verifier's note landed in
  the task folder, got committed, and was deleted at WRAP.
- **Retro:** `wrap-task` §0 appends findings to
  `docs/Feature_Planning/loop-improvements/00-request.md`, creating the entry
  with `add-task` if it is missing.
- **The e2e teardown also stops the dev app on `:80`**, discovered while
  testing. Documented in `verify-visually` and `e2e/README.md`; the fix is
  collector item 1.

## Where the change lives

| Concern | Path |
|---------|------|
| Checkpoints, refactor-first | `.agents/skills/plan-phases/SKILL.md`, `.agents/loop/templates/03-plan.md` |
| Checkpoint dispatch | `.agents/skills/next-task/SKILL.md`, `.agents/skills/continue-task/SKILL.md` |
| Build-first, checkpoint mode, e2e know-how | `.agents/skills/verify-visually/SKILL.md`, `e2e/README.md`, `.agents/skills/run-app/SKILL.md` |
| Folded agent-memory notes | `.agents/skills/implement-phase/SKILL.md` (stale manifest, `public/hot`, Pest/deptrac), `.agents/skills/design-architecture/SKILL.md` (provider edges) |
| Retro | `.agents/skills/wrap-task/SKILL.md` §0 |
| Blade → build | `scripts/gate.js` |

## Decisions worth remembering

- The scripts stay strict about `--`; only the docs changed (#6).
- The retro is part of WRAP, not a seventh step, and works from artifacts only (#2).
- DESIGN, PLAN and VERIFY were skipped for this task at the user's call (#7).

## Not done

- The scripts do not tolerate a stray `--` (non-goal).
- No tool-agnostic agents folder or Codex equivalents (non-goal).
- The local `.claude/agent-memory/` notes are now inert, since their content
  was folded in. The folder is gitignored and was left on disk for the user to
  delete.
- Retro findings from this task are in `loop-improvements/` (e2e teardown
  kills `:80`, the gate needs Sail for docs-only changes, no light path for
  tooling tasks).
