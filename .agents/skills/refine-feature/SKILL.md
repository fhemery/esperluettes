---
name: refine-feature
description: Interview the user to turn a rough feature request into a complete functional specification. Use at the REFINE step of the loop, or whenever a request is too vague to build from — "I want a diary on chapters", "add badges". Walks this app's cross-cutting checklist (roles, privacy, notifications, events, lifecycle) and writes §1 of docs/Feature_Planning/<slug>/CONTEXT.md, with a complexity rating out of 5.
---

# Refine a feature

Turn `00-request.md` into §1 of `CONTEXT.md`: a specification where every
statement is either something the user confirmed or an assumption written down
as one.

**Run this in the `feature-refiner` subagent.** It has a fresh context and can
ask the user directly (Claude Code: `AskUserQuestion`), but only when a point is
**blocking**. Delegate *research* to read-only agents.

A point is **blocking** when it is the user's call, a reasonable answer could go
either way, and a wrong guess would change the spec's shape or be costly to undo
(who sees what, what happens on delete, a notification that may leak). Labels,
ordering and wording are never blocking: pick the precedent and record it.

**No blocking point → do not interview.** Write the spec from the request plus
codebase precedent, put every judgement call in the assumptions table of
`CONTEXT.md` §4, commit, and hand back so the loop continues without waiting
for the user. If the host gives you no way to ask, stop at the first blocking point
and report it with your recommended default; the orchestrator asks and
re-dispatches you.

Output: `docs/Feature_Planning/<slug>/CONTEXT.md` (created by `add-task` from
[`templates/CONTEXT.md`](../../loop/templates/CONTEXT.md); copy it if missing):
§1 filled in, rows in §4, the **complexity** line in the header, and a §0
Handoff entry. Read §0 first — an earlier session may have left one.

## Rate the complexity (1–5)

Write the rating in the `CONTEXT.md` header, from what the spec turned out to
need — not from the request's length:

| Rating | Looks like |
|--------|------------|
| 1 | One-file tweak, copy change, config value. No new test shape. |
| 2 | One domain, no new table, no new permission rule, no new notification; a handful of files. |
| 3 | A normal feature: a table or a route or a permission rule, in one domain. |
| 4 | Several domains, or a new extension point / registry, or a cross-domain flow. |
| 5 | A new domain, several tables, or more than ~7 phases' worth of work. |

The rating routes the rest of the loop (see `next-task` §4):

- **1–2 — fast path.** Do not hand back yet. After §1, read
  `design-and-plan` and write §2 and §3 yourself, in this same context (a
  compact design, usually one phase). One agent, one startup, one read of the
  spec; the orchestrator then goes straight to BUILD. Rate 2 only if you are
  confident; any doubt → 3.
- **3–4** — hand back; the `architect-planner` agent takes DESIGN + PLAN.
- **5** — same, and `architect-planner` will try to split the work. Name in §0
  Handoff the natural seams you saw (which flows can ship alone).

## Step 1 — Understand before asking

Read `00-request.md`, then find out what the codebase already decides. Spawn a
read-only research agent (`Explore`) with a concrete list of questions —
"does `StoryPublicApi` expose an authorship check?", "how does the Quote domain
register its profile tab?", "what does `ProfileTabRegistry` require?".

Look at the nearest existing feature. This app has strong precedent: Quote,
Comment, ReadList and Annotations all solve the same shape of problem. Reusing
a settled pattern is worth more than inventing a better one. If a similar
feature has already shipped, its compact record is in
`docs/Feature_Planning/_done/<slug>.md` — the "Decisions worth remembering" and
"Not done" sections there often answer a question before you have to ask it.

**Never ask the user something the code answers.** Every wasted question spends
the attention you need for the questions only they can answer.

## Step 2 — Interview (blocking points only)

Triage first: list what you cannot settle from the request, the code or a
prior feature, and keep only the blocking ones. Interview on those; the rest
become assumptions. If the list is empty, skip to Step 3.

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
3. Genuinely the user's call **and blocking** → ask. Genuinely the user's call
   but non-blocking → record your recommended default as an assumption.

The most commonly forgotten items in this app — settle each explicitly every
time (ask if blocking, otherwise assume and record):

- **non-confirmed `user` vs `user-confirmed`** — the distinction is invisible in
  most requests and load-bearing in most features;
- **what happens when the acting user is deactivated or deleted**;
- **what happens when the parent entity disappears**, and what the UI shows;
- **whether anyone gets notified**, and whether the notification leaks something
  the spec calls private.

## Step 4 — Replay

Before writing the document, read the whole flow back as a short narrative —
"a confirmed reader highlights a passage, …" — including the edge paths.
Reading it back catches contradictions that a question list never does. If you
interviewed, say it to the user; if not, put it in your head and fix what
doesn't hold.

Then list what you are **assuming without having asked**. It goes in the
assumptions table and in your summary, so the user can veto it later.

## Step 5 — Write

Fill §1 of `CONTEXT.md`. Rules:

- No requirement the user did not confirm. If you needed one to make the spec
  coherent, it goes in §1.8 as an open question or in the assumptions table.
- Every decision goes in the §4 decisions table (once — §1 does not mirror it).
- Mark each open question **blocking** or **non-blocking**. The step cannot
  close with a blocking one open.
- §1.7 (out of scope) is not optional. An unstated non-goal will be built by
  accident.
- User-facing wording in French.
- End with the §0 Handoff entry (`REFINE → DESIGN+PLAN`): the assumptions the
  user is likeliest to reverse, parked technical constraints, the seams (at 5).

Run `pnpm run gate` and commit `CONTEXT.md` (follow the
`commit` skill) — this step wrote or deleted planning docs (e.g. absorbing a
sibling task), and an uncaught broken link here is exactly what sits invisible
until an unrelated change stumbles on it later.

Then hand back to the orchestrator with a five-line summary: what the feature
is, the complexity rating, the two or three decisions that shaped it, the assumptions the user is most
likely to want to reverse, and anything still open (non-blocking only — a
blocking one is asked, never left open).

## In `auto` mode

Never interview, even for a blocking point you would otherwise ask: record your
recommended default as an assumption and flag it in your summary.
