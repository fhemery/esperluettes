---
name: design-and-plan
description: Turn the functional spec in CONTEXT.md §1 into a technical design (§2) and a phased implementation plan (§3) in one pass, and split a rated-5 task into several backlog tasks. Use at the DESIGN+PLAN step of the loop, or from the refiner's fast path on a rated 1–2 task. Defers to design-architecture and plan-phases for the method.
---

# Design and plan, in one pass

One agent, one context: it designs, then phases what it designed. The old
hand-off between DESIGN and PLAN existed because they were two agents; with one
the design never has to be re-read, so the two documents stop drifting.

Read `CONTEXT.md` §0 (Handoff), §1 and §4, then follow in order:

0. **Review §1 first when it was cheap to write** — the refiner runs on a
   lighter model. If the complexity is 4–5, or the refiner's Handoff says it
   asked two or more blocking questions, check §1 against `00-request.md` and
   the code before designing on it: roles and visibility, lifecycle, the
   cross-cutting checklist, anything contradicted by precedent. Fix gaps in
   place (log them in §4); ask only if a gap is blocking. Skip this on 3.
1. [`design-architecture`](../design-architecture/SKILL.md) → writes **§2**.
   Its tradeoff rule applies unchanged: ask the user only about a **blocking**
   tradeoff; decide and record the rest in §4.
2. **Size check** (below) — before phasing.
3. [`plan-phases`](../plan-phases/SKILL.md) → writes **§3**. Because you wrote
   §2 a minute ago, "stay on your side of the line" means: §2 holds shape and
   contracts, §3 holds paths, signatures and named tests, and neither repeats
   the other.

Commit once (`CONTEXT.md`) after §3, with the gate green. The §0 Handoff entry
(`DESIGN+PLAN → BUILD`) holds what BUILD cannot read off the plan: the riskiest
phase, the assumption most likely to be reversed, anything verified in the code
that a later reader would otherwise re-check.

## Fast path (complexity 1–2)

When the refiner calls you inline, write a **compact** §2 (only the subsections
that have something to say; "no new edge" is one line) and a §3 with the
fewest phases that each end green — one is normal. Skip checkpoint rows unless
existing consumers are reshaped. The method is the same; the output is short.

## Size check and split (complexity 5)

After §2, count the phases §3 would need. If the rating is 5, or the plan would
exceed ~7 phases, try to split:

1. Find a first slice that ships on its own, delivers user-visible value, and
   leaves no half-built surface. Cut along the seams the refiner noted in §0
   and along the design (a registry before its consumers is a slice; a table
   without its UI is not).
2. Keep **only the first slice** in this task: narrow §1.7 (out of scope) to
   say what moved out, and plan §3 for the slice alone.
3. Send every remaining slice to the backlog **immediately**, following the
   `add-task` command's procedure. Insert them last-slice-first with
   `--position=top`, so they end up in dependency order at the head of the
   TODO list:

   ```bash
   pnpm run add-task --slug=<slug> --title="<title>" --mode=interactive --position=top
   ```

   This task's folder is **deleted at WRAP**, so each `00-request.md` must be
   self-contained: restate the relevant part of §1, the decisions it inherits
   from §4 and the design constraints from §2, and name the task that ships
   before it (by slug; `_done/<slug>.md` once wrapped). Do not invent
   requirements; mark anything unsure as a question for its own REFINE.
4. Record the split in §4 (decision: "split into N tasks", with the slugs) and
   in the §0 Handoff, so the post-PLAN review surfaces it.

If no honest first slice exists (the pieces only work together), do not split:
say so in §0 and plan it whole. A forced split is worse than a big task.

## Hand back

Five lines: placement, data model in one line, tradeoffs (asked vs assumed),
any new deptrac edge; then the phase index with the total count (checkpoints
included) and the backlog rows created by a split.
