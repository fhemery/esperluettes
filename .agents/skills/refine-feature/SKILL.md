---
name: refine-feature
description: Interview the user to turn a rough feature request into a complete functional specification. Use at the REFINE step of the loop, or whenever a request is too vague to build from — "I want a diary on chapters", "add badges". Walks this app's cross-cutting checklist (roles, privacy, notifications, events, lifecycle) and produces docs/Feature_Planning/<slug>/01-functional.md.
---

# Refine a feature

Turn `00-request.md` into `01-functional.md`: a specification where every
statement is either something the user confirmed or an assumption written down
as one.

**Run this in the main thread.** You need to talk to the user. Delegate
*research* to read-only agents; never delegate the interview.

Output: `docs/Feature_Planning/<slug>/01-functional.md` from
[`templates/01-functional.md`](../../loop/templates/01-functional.md), plus rows
appended to `DECISIONS.md`.

## Step 1 — Understand before asking

Read `00-request.md`, then find out what the codebase already decides. Spawn a
read-only research agent (`Explore`) with a concrete list of questions —
"does `StoryPublicApi` expose an authorship check?", "how does the Quote domain
register its profile tab?", "what does `ProfileTabRegistry` require?".

Look at the nearest existing feature. This app has strong precedent: Quote,
Comment, ReadList and Annotations all solve the same shape of problem. Reusing
a settled pattern is worth more than inventing a better one.

**Never ask the user something the code answers.** Every wasted question spends
the attention you need for the questions only they can answer.

## Step 2 — Interview

The discipline, in order of importance:

1. **One question at a time.** Never a numbered list of eight questions. The
   user's answer to question 1 usually changes questions 2 through 8.
2. **Closed questions with a recommended default.** Not "how should visibility
   work?" but "Default visibility: private (like the quote book) or public?
   I'd take private — it is what every other reader-owned surface here does."
   Give 2–4 options, put your recommendation first, say why in one clause.
3. **Ask the consequential ones first.** Ordering, colours and labels can be
   decided during implementation. Who can see what, and what happens on delete,
   cannot.
4. **Follow the interesting answer.** When an answer opens a hole — "readers can
   see other readers' entries" → "including on private chapters?" — chase it
   before returning to the checklist.
5. **Say when you disagree.** If the user picks something that will hurt, say so
   in one sentence, then record their decision and move on. Rule #1 of
   `AGENTS.md`: surface tradeoffs, do not hide confusion.
6. **Stay functional.** "What happens when the author deletes the chapter" is
   functional. "Do we soft-delete or nullify the FK" is DESIGN. If the user
   volunteers a technical constraint, park it in a note for DESIGN.

Batch only when questions are genuinely independent and cheap (labels, wording,
sort order) — then a short multiple-choice group is fine.

## Step 3 — Walk the checklist

Go through
[`cross-cutting-checklist.md`](../../loop/references/cross-cutting-checklist.md).
For each item, decide in this order:

1. Not applicable → note "N/A — <reason>", say nothing to the user.
2. The code or an existing feature settles it → note the answer, mention it in
   passing, do not ask.
3. Genuinely the user's call → ask.

The most commonly forgotten items in this app, ask them explicitly every time:

- **non-confirmed `user` vs `user-confirmed`** — the distinction is invisible in
  most requests and load-bearing in most features;
- **what happens when the acting user is deactivated or deleted**;
- **what happens when the parent entity disappears**, and what the UI shows;
- **whether anyone gets notified**, and whether the notification leaks something
  the spec calls private.

## Step 4 — Replay

Before writing the document, read the whole flow back to the user as a short
narrative — "a confirmed reader highlights a passage, …" — including the edge
paths. Reading it back catches contradictions that a question list never does.

Then list what you are **assuming without having asked**, so the user can veto.

## Step 5 — Write

Fill the template. Rules:

- No requirement the user did not confirm. If you needed one to make the spec
  coherent, it goes in §9 as an open question or in the assumptions table.
- Every decision goes in the §7 table **and** in `DECISIONS.md`.
- Mark each open question **blocking** or **non-blocking**. The step cannot
  close with a blocking one open.
- §8 (out of scope) is not optional. An unstated non-goal will be built by
  accident.
- User-facing wording in French.

Then hand back to the orchestrator with a five-line summary: what the feature
is, the two or three decisions that shaped it, and anything still open.

## In `auto` mode

Do not interview. Write the spec from the request plus codebase precedent, put
every judgement call in the assumptions table of `DECISIONS.md`, and flag in
your summary the ones the user is most likely to want to reverse.
