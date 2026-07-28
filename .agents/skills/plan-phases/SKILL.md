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
2. **A phase ends green.** `npm run gate` passes at the end of every phase. If a
   phase cannot end green, it is badly cut.
3. **A phase is S or M.** S ≈ half a day, M ≈ 1–2 days. Anything larger gets
   split. Prefer more, smaller phases — each one is a fresh subagent context.
4. **Shared infrastructure first.** Anything another planned feature will reuse
   (a JS helper, a component slot, a registry) goes in its own early phase and
   is called out as such.
5. **Server-side security lands before the UI that relies on it.** The policy
   phase always precedes the endpoint phase; never ship a UI whose only
   protection is the absence of a button.
6. **Order by dependency, not by excitement.** If the user wants to see
   something on screen early, insert a thin vertical slice as an explicit
   phase — do not reorder the safety phases away.

## Per phase, write

- **Goal** — one sentence.
- **Deliverables** — the actual files, by path.
- **Tests** — named integration tests, as they will exist. The default level is
  a Laravel feature test hitting the route with a real user of a real role. Unit
  tests only for genuinely isolated logic (sanitisers, anchoring, formatters).
  Vitest for DOM behaviour.
- **Acceptance** — checkable statements. "✅ A non-confirmed user gets 403 on
  POST /quotes", not "✅ permissions work". Always include "✅ `npm run gate`
  green".

Write the **test that proves the security rule** into the phase that introduces
the rule, not into a later "hardening" phase.

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

Return the phase index table and the total phase count. Flag any phase you were
unsure how to cut.
