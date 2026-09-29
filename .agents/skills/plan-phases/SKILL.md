---
name: plan-phases
description: Break an architecture document into shippable, independently testable phases. Use at the PLAN step of the loop, or when asked to phase a feature that already has a technical design. Produces docs/Feature_Planning/<slug>/03-plan.md with a phase index, per-phase deliverables/tests/acceptance, and the visual QA checklist.
---

# Phase the development

Turn `02-architecture.md` into `03-plan.md`: an ordered list of phases, each one
independently shippable, testable, and revertable.

Output:
[`templates/03-plan.md`](../../loop/templates/03-plan.md).

## Read first

`01-functional.md`, `02-architecture.md`, `DECISIONS.md`. Do not re-open
decisions recorded there; if one is unimplementable, say so in "Open items"
rather than quietly changing it.

## Slicing rules

1. **Bottom-up.** Schema and model, then policy and lifecycle listeners, then
   service and public API, then endpoints, then UI, then i18n/a11y polish. This
   is the order that keeps every phase testable on its own.
2. **A phase ends green.** `pnpm run gate` passes at the end of every phase. If a
   phase cannot end green, it is badly cut.
3. **A phase is S or M.** S ≈ half a day, M ≈ 1–2 days. Anything larger gets
   split. Prefer more, smaller phases — each one is a fresh subagent context.
4. **Shared infrastructure first.** Anything another planned feature will reuse
   (a JS helper, a component slot, a registry) goes in its own early phase and
   is called out as such.
5. **Refactor before the feature.** When the feature needs existing code
   reshaped, that reshaping is its own phase(s), first, with **no behaviour
   change** — its tests are the existing ones, still green. The feature phases
   come after. Make the change easy, then make the easy change. Mark such a
   phase `Refactor —` in the index.
6. **Checkpoint after a shared reshape.** Right after any phase that reshapes
   code with **existing consumers** — a refactor, or infrastructure other
   features already use — insert a checkpoint row (see below). Elsewhere, add
   one wherever a regression would be expensive to trace later.
7. **Server-side security lands before the UI that relies on it.** The policy
   phase always precedes the endpoint phase; never ship a UI whose only
   protection is the absence of a button.
8. **Order by dependency, not by excitement.** If the user wants to see
   something on screen early, insert a thin vertical slice as an explicit
   phase — do not reorder the safety phases away.

## Each phase is read alone

A `phase-implementer` subagent reads **its phase and nothing else** of this
document — not the neighbouring phases, and of `02-architecture.md` only the
sections your phase names. So each phase must stand on its own: name the
architecture sections it depends on (`architecture §3.5`), and state the
outcome of earlier phases it builds on in a clause rather than assuming the
reader saw them.

This document owns the **file-by-file detail**. `02-architecture.md` gives shape
and contracts; you turn that into paths, signatures and named tests. Reference
its sections instead of restating their prose — but do not send BUILD hunting
through it for something you could have written in a line.

## Per phase, write

- **Goal** — one sentence.
- **Deliverables** — the actual files, by path.
- **Tests** — named integration tests, as they will exist. The default level is
  a Laravel feature test hitting the route with a real user of a real role. Unit
  tests only for genuinely isolated logic (sanitisers, anchoring, formatters).
  Vitest for DOM behaviour.
- **Acceptance** — checkable statements. "✅ A non-confirmed user gets 403 on
  POST /quotes", not "✅ permissions work". Always include "✅ `pnpm run gate`
  green".

Write the **test that proves the security rule** into the phase that introduces
the rule, not into a later "hardening" phase.

## Checkpoint rows

A checkpoint checks that **existing** behaviour survived the phase before it,
before later phases build on top. It changes no code, and it is run by the
`visual-verifier`, not a `phase-implementer`.

- Id: the preceding phase's number plus `v` — `2v` follows phase 2. It counts
  in the BUILD counter like any row.
- Its section is titled `## Checkpoint <id> — <what>` and names the existing
  consumers to look at (pages, roles) and the e2e specs to run. It has no
  Deliverables or Tests.
- Output: screenshots under `shots/checkpoint-<id>/`, and a
  `**Result (<date>, HEAD <sha>) — PASS|FAIL**` block appended to its section.
- A FAIL sends a `phase-implementer` back to fix the preceding phase; the
  checkpoint then runs again.

## Visual QA checklist

Fill the checklist table at the bottom of the template *now*, while the flows
are fresh — one row per surface worth looking at with real eyes. VERIFY executes
it. Cover at minimum: the happy path, the empty state, each role that sees a
different thing, mobile, and every state named in §5 of the functional spec
(deleted parent, deactivated user, stale data).

## Open items

Anything the plan assumes but has not verified — a method that may not exist, a
registry contract you have not read. Each must name the phase that needs it, so
it gets resolved before that phase starts. Verify what you cheaply can *now*
rather than leaving it to BUILD.

## Output

Run `pnpm run gate` and commit `03-plan.md` (follow the `commit` skill), then
return the phase index table and the total phase count (checkpoints included).
Flag any phase you were unsure how to cut.
