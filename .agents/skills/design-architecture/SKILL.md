---
name: design-architecture
description: Turn a functional specification into a technical design, letting the user arbitrate the real tradeoffs. Use at the DESIGN step of the loop, or when asked "how should we build X" for a spec that already exists. Produces docs/Feature_Planning/<slug>/02-architecture.md with domain placement, data model, public API, deptrac impact and locked tradeoffs.
---

# Design the architecture

Turn `01-functional.md` into `02-architecture.md`: how the feature is built, with
every real fork in the road arbitrated by the user rather than by you.

**Run this in the main thread** — the user picks the tradeoffs. Delegate
research to read-only agents.

Output:
[`templates/02-architecture.md`](../../loop/templates/02-architecture.md), plus
rows in `DECISIONS.md`.

## Step 1 — Ground yourself in the existing code

Read `01-functional.md` and `DECISIONS.md`, then investigate — yourself or via
research agents:

- the **nearest analogous feature** and how it is wired (Quote is the most
  recent complete example: policy, public API, profile tab, notification,
  cascade listeners) — its compact record, if wrapped, is
  `docs/Feature_Planning/_done/<slug>.md`, with the tradeoffs it locked and why;
- the **extension points** the feature will plug into, and their real
  signatures — `ProfileTabRegistry`, `SettingsPublicApi`, `NotificationPublicApi`,
  `ModerationRegistry`, `MediaPublicApi`, `EventPublicApi`, `StatisticDefinition`;
- `deptrac.yaml` — which edges already exist between the domains involved;
- `docs/Domain_Structure.md` and `docs/Architecture.md`.

Verify signatures rather than remembering them. A design built on a method that
does not exist costs a whole BUILD phase.

## Step 2 — Identify the real tradeoffs

A real tradeoff is one where a reasonable engineer could pick either side and
the choice is expensive to reverse. Typically:

- **domain placement** — new domain vs. extend an existing one;
- **extension point vs. direct call** — does the other domain need a registry,
  or is a public-API call enough? Adding an extension point is the more
  expensive, more reusable answer;
- **data shape** — new table vs. columns on an existing one; denormalised
  counter vs. computed on read;
- **enforcement point** — policy class, form request, query scope, or all three;
- **server-rendered vs. Alpine/JS** — this app leans Blade-first;
- **eager vs. lazy** — an extra join on a hot page (chapter read, home, profile)
  is a decision, not a detail.

Anything that is not one of these, decide yourself and state it. Do not make the
user arbitrate naming or folder layout.

## Step 3 — Present each tradeoff

One at a time, in this shape:

> **<the question>**
> - **A (recommended)** — <option>. <one-line consequence>
> - **B** — <option>. <one-line consequence>
>
> I'd take A because <reason>. B is worth it if <the condition that would flip
> it>.

Always give your recommendation and always name what would change your mind. If
the user picks the other option, record it without argument and follow through
properly — a half-hearted implementation of the rejected option is worse than
either.

Flag any place where the functional spec is technically expensive: "§4.3 as
written costs a query per chapter on the profile tab; capping at 50 entries
makes it one query — acceptable?" That is a functional question surfaced by
design; take it back to the user rather than solving it silently.

## Step 4 — Write

Fill the template. Non-negotiables for this codebase:

- domain boundaries respected; new deptrac edges listed and justified in §5;
- controllers call services, never models;
- foreign keys only within the owning domain;
- PHP attribute syntax on models (`#[Table]`, `#[Fillable]`, …);
- migrations with a `down()`, named `YYYY_MM_DD_HHiiss_*`, indexed for the
  query patterns in §4 of the spec;
- **no `PATCH` routes** — the production WAF blocks the verb; use `PUT`;
- French lang files for every user-visible string;
- integration tests as the default level (§6 of the template).

§7 records every tradeoff **with the rejected options** — that is what stops the
question being re-opened in three months.

### Stay on your side of the line

You write **shape and contracts**: signatures, data shapes, enforcement points,
deptrac edges, the reasoning behind each. You do not write the change list —
"in `ActivityController`, replace the `ImageService` dependency, delete the
`deleteWithVariants()` call, add `resolveImage()`" is PLAN's sentence, not
yours. Yours is "activity image storage moves behind `MediaPublicApi`;
deletion is deferred to the sweep."

The test: if a line would be copied verbatim into a phase's Deliverables, it
belongs in `03-plan.md` only. Duplicating it does not help BUILD — BUILD reads
the plan — and guarantees the two drift.

Hand back a five-line summary: placement, data model in one line, the tradeoffs
locked, and any new deptrac edge.

## In `auto` mode

Pick the recommended option everywhere, record each as an assumption in
`DECISIONS.md`, and keep the design as small as the fix allows. If a tradeoff
turns out to be genuinely expensive to reverse, stop and ask anyway — `auto` is
about speed on small things, not about taking large decisions alone.
